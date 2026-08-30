<?php

namespace App\Application\Users;

use App\Application\Shared\Result;
use App\History\AdminHistory;
use App\History\TokenHistory;
use App\History\UserHistory;
use App\Libraries\JWTLibrary;
use App\Models\BanModel;
use App\Models\BlacklistModel;
use App\Models\RefreshTokenModel;
use App\Models\TokenBlacklistModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use EmailValidator\EmailValidator;

class UserService
{
    private function verifyEmail(string $email): Result
    {
        try {
            $disposableDomains = [
                '10minutemail.com', 'guerrillamail.com', 'mailinator.com',
                'tempmail.com', 'throwaway.email', 'yopmail.com',
                'trashmail.com', 'sharklasers.com', 'mailnator.com',
                'maildrop.cc', 'getairmail.com', 'getnada.com',
                'temp-mail.org', 'emailfake.com', 'fakeinbox.com',
                'wegwerfmail.de', 'jetable.org', 'sneakemail.com',
                'spamgourmet.com', 'mintemail.com',
            ];
            $emailValidator = new EmailValidator([
                'checkMxRecords' => false,
                'checkDisposableEmail' => true,
                'LocalDisposableOnly' => true,
                'disposableList' => $disposableDomains,
            ]);

            $isValid = $emailValidator->validate($email);

            if (!$isValid) {
                $errorReason = $emailValidator->getErrorReason();
                return Result::fail([
                    'error' => 'L\'adresse e-mail n\'est pas valide.',
                    'reason' => $errorReason,
                ], 400);
            }

            return Result::ok();
        } catch (\Exception $e) {
            log_message('error', 'Email validation failed: ' . $e->getMessage());
            return Result::fail(['error' => 'Validation email impossible.'], 500);
        }
    }

    public function register(array $input, RequestInterface $request): Result
    {
        $model = new UserModel();

        $email = $input['email'] ?? null;

        $data = [
            'email' => $email,
            'password' => $input['password'] ?? null,
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'birth_date' => $input['birth_date'] ?? null,
            'country' => $input['country'] ?? null,
            'address' => $input['address'] ?? null,
            'role' => 'user',
        ];

        $missing = $this->validateRequired($data, ['email', 'password', 'first_name', 'last_name']);
        if (!empty($missing)) {
            return Result::fail([
                'error' => lang('Users.errors.required_fields'),
                'missing' => $missing,
            ], 400);
        }

        // Verify email with EmailValidator
        $emailVerification = $this->verifyEmail($email);
        if (!$emailVerification->isSuccess()) {
            return $emailVerification;
        }

        // Check blacklist
        $ip = $request->getIPAddress();
        if ((new BlacklistModel())->isBlacklisted($data['email'], $ip)) {
            return Result::fail('Inscription non autorisée.', 403);
        }

        // Sanitize email to avoid duplicates (remove Gmail plus trick)
        try {
            $emailValidator = new EmailValidator();
            $emailValidator->validate($email);
            if ($emailValidator->isGmailWithPlusChar()) {
                $data['email'] = $emailValidator->getGmailAddressWithoutPlus();
            }
        } catch (\Exception $e) {
            log_message('error', 'Failed to sanitize email: ' . $e->getMessage());
        }

        if (!$model->insert($data)) {
            return Result::fail($model->errors(), 400);
        }

        $userId = $model->getInsertID();
        $user = $model->getUserById($userId);

        $tokens = $this->issueTokens($user, $request);

        (new UserHistory())->logRegister($request, $user);

        return Result::created([
            'message' => lang('Users.register.success'),
            'user' => $user,
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    public function login(array $input, RequestInterface $request): Result
    {
        $model = new UserModel();

        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!$email || !$password) {
            return Result::fail(lang('Users.login.required'), 400);
        }

        $maxAttempts = (int) (getenv('LOGIN_MAX_ATTEMPTS') ?: 5);
        $lockMinutes = (int) (getenv('LOGIN_LOCK_MINUTES') ?: 15);

        $userRecord = $model->getUserForLogin($email);

        if (!$userRecord || !($userRecord['is_active'] ?? false)) {
            $ban = $userRecord ? (new BanModel())->getActiveBan($userRecord['id']) : null;
            (new UserHistory())->logLoginFailed($request, $email, $userRecord['id'] ?? null, $ban ? 'banned' : 'invalid_credentials');
            if ($ban) {
                return Result::fail(['error' => 'Votre compte a été suspendu. Motif : ' . $ban['reason']], 403);
            }
            return Result::fail(lang('Users.login.invalid'), 401);
        }

        if (!empty($userRecord['locked_until']) && strtotime($userRecord['locked_until']) > time()) {
            (new UserHistory())->logLoginFailed($request, $email, $userRecord['id'] ?? null, 'locked');
            return Result::fail(lang('Users.login.locked'), 423);
        }

        if (!password_verify($password, $userRecord['password_hash'])) {
            $failed = ((int) ($userRecord['failed_login_count'] ?? 0)) + 1;
            $lockedUntil = null;
            if ($failed >= $maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
            }
            $model->recordLoginFailure($userRecord['id'], $failed, $lockedUntil);
            (new UserHistory())->logLoginFailed(
                $request,
                $email,
                $userRecord['id'] ?? null,
                $lockedUntil ? 'locked' : 'invalid_password'
            );

            if ($lockedUntil) {
                return Result::fail(lang('Users.login.locked'), 423);
            }

            return Result::fail(lang('Users.login.invalid'), 401);
        }

        $model->recordLoginSuccess($userRecord['id']);
        $user = $model->getUserById($userRecord['id']);

        $tokens = $this->issueTokens($user, $request);

        (new UserHistory())->logLogin($request, $user);

        return Result::ok([
            'message' => lang('Users.login.success'),
            'user' => $user,
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    public function profile(?string $userId): Result
    {
        return $this->findUserById($userId);
    }

    public function updateProfile(?string $userId, array $input, RequestInterface $request): Result
    {
        return $this->updateUserRecord((string) $userId, $input, $request, false);
    }

    public function deleteProfile(?string $userId, RequestInterface $request): Result
    {
        return $this->deleteUserRecord(
            (string) $userId,
            static fn($before) => (new UserHistory())->logProfileDelete($request, (string) $userId, $before),
            'Users.errors.delete_account',
            'Users.profile.deleted'
        );
    }

    public function createWorker(array $input, RequestInterface $request): Result
    {
        $model = new UserModel();

        $data = [
            'email' => $input['email'] ?? null,
            'password' => $input['password'] ?? null,
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'role' => $input['role'] ?? 'worker',
            'department' => $input['department'] ?? null,
            'position' => $input['position'] ?? null,
            'hire_date' => $input['hire_date'] ?? null,
            'cin' => $input['cin'] ?? null,
            'profile_image' => $input['profile_image'] ?? null,
        ];

        $missing = $this->validateRequired($data, ['email', 'password', 'first_name', 'last_name']);
        if (!empty($missing)) {
            return Result::fail([
                'error' => 'Champs obligatoires manquants.',
                'missing' => $missing,
            ], 400);
        }

        if (!in_array($data['role'], ['admin', 'worker'], true)) {
            return Result::fail(['error' => 'Rôle invalide. Autorisés : admin, worker'], 400);
        }

        $emailVerification = $this->verifyEmail($data['email']);
        if (!$emailVerification->isSuccess()) {
            return $emailVerification;
        }

        if (!$model->insert($data)) {
            return Result::fail($model->errors(), 400);
        }

        $userId = $model->getInsertID();
        $user = $model->getUserById($userId);

        return Result::created([
            'message' => 'Employé créé avec succès.',
            'user' => $user,
        ]);
    }

    public function importWorkersCSV(string $csvContent, RequestInterface $request): Result
    {
        $lines = array_filter(array_map('trim', explode("\n", $csvContent)));
        if (count($lines) < 2) {
            return Result::fail(['error' => 'Le fichier CSV est vide ou ne contient pas de données.'], 400);
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map('strtolower', array_map('trim', $header));

        $required = ['email', 'password', 'first_name', 'last_name'];
        $missing = array_diff($required, $header);
        if (!empty($missing)) {
            return Result::fail(['error' => 'Colonnes manquantes dans le CSV : ' . implode(', ', $missing)], 400);
        }

        $model = new UserModel();
        $created = [];
        $errors = [];
        $lineNum = 1;

        foreach ($lines as $line) {
            $lineNum++;
            if (empty($line)) continue;

            $values = str_getcsv($line);
            $row = array_combine($header, $values);

            $data = [
                'email' => $row['email'] ?? null,
                'password' => $row['password'] ?? null,
                'first_name' => $row['first_name'] ?? null,
                'last_name' => $row['last_name'] ?? null,
                'phone' => $row['phone'] ?? null,
                'role' => $row['role'] ?? 'worker',
                'department' => $row['department'] ?? null,
                'position' => $row['position'] ?? null,
                'hire_date' => $row['hire_date'] ?? null,
                'cin' => $row['cin'] ?? null,
            ];

            $missingFields = $this->validateRequired($data, $required);
            if (!empty($missingFields)) {
                $errors[] = ['line' => $lineNum, 'email' => $data['email'] ?? 'N/A', 'error' => 'Champs manquants : ' . implode(', ', $missingFields)];
                continue;
            }

            if (!in_array($data['role'], ['admin', 'worker'], true)) {
                $data['role'] = 'worker';
            }

            if (!$model->insert($data)) {
                $modelErrors = $model->errors();
                $errors[] = ['line' => $lineNum, 'email' => $data['email'], 'error' => implode(', ', $modelErrors)];
                continue;
            }

            $created[] = ['line' => $lineNum, 'email' => $data['email'], 'role' => $data['role']];
        }

        return Result::ok([
            'message' => count($created) . ' employé(s) importé(s), ' . count($errors) . ' erreur(s).',
            'created' => $created,
            'errors' => $errors,
        ]);
    }

    public function listUsers(): Result
    {
        $model = new UserModel();
        return Result::ok($model->getAllUsers());
    }

    public function getUser(?string $id): Result
    {
        return $this->findUserById($id);
    }

    public function updateUser(?string $id, array $input, ?string $actorId, RequestInterface $request): Result
    {
        return $this->updateUserRecord((string) $id, $input, $request, true, $actorId);
    }

    public function deleteUser(?string $id, ?string $actorId, RequestInterface $request): Result
    {
        return $this->deleteUserRecord(
            (string) $id,
            static fn($before) => (new AdminHistory())->logUserDelete($request, $actorId, (string) $id, $before),
            'Users.errors.delete_user',
            'Users.admin.deleted'
        );
    }

    public function refreshToken(?string $refreshToken, RequestInterface $request): Result
    {
        if (!$refreshToken) {
            return Result::fail(lang('Users.refresh.required'), 400);
        }

        $model = new RefreshTokenModel();
        $hash = hash('sha256', $refreshToken);
        $record = $model->where('token_hash', $hash)->first();

        if (!$record || $record['revoked_at'] !== null) {
            return Result::fail(lang('Users.refresh.invalid'), 401);
        }

        if (strtotime($record['expires_at']) < time()) {
            return Result::fail(lang('Users.refresh.expired'), 401);
        }

        $userModel = new UserModel();
        $user = $userModel->getUserById($record['user_id']);
        if (!$user) {
            return Result::notFound(lang('Users.errors.not_found'));
        }

        $tokens = $this->issueTokens($user, $request);
        $token = $tokens['token'];
        $newRefresh = $tokens['refresh_token'];
        $newRefreshId = $this->getRefreshTokenId($newRefresh);

        $model->update($record['id'], [
            'revoked_at' => date('Y-m-d H:i:s'),
            'replaced_by' => $newRefreshId,
        ]);

        $jwt = new JWTLibrary();
        $decoded = $jwt->decode($token);
        $jti = $decoded->jti ?? null;
        (new TokenHistory())->log(
            $request,
            'refresh',
            $user['id'],
            $jti,
            $newRefreshId,
            ['refresh_token_rotated' => true]
        );

        return Result::ok([
            'token' => $token,
            'refresh_token' => $newRefresh,
        ]);
    }

    public function logout(?string $refreshToken, ?string $authHeader, RequestInterface $request): Result
    {
        if ($refreshToken) {
            $model = new RefreshTokenModel();
            $hash = hash('sha256', $refreshToken);
            $record = $model->where('token_hash', $hash)->first();
            if ($record && $record['revoked_at'] === null) {
                $model->update($record['id'], [
                    'revoked_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $jwt = new JWTLibrary();
            $decoded = $jwt->decode($token);
            if ($decoded && isset($decoded->jti, $decoded->exp)) {
                $blacklist = new TokenBlacklistModel();
                $blacklist->insert([
                    'id' => $this->uuidV4(),
                    'jti' => $decoded->jti,
                    'expires_at' => date('Y-m-d H:i:s', (int) $decoded->exp),
                    'revoked_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'reason' => 'logout',
                ]);
                (new TokenHistory())->log(
                    $request,
                    'logout',
                    $decoded->user_id ?? null,
                    $decoded->jti,
                    $refreshToken ? $this->getRefreshTokenId($refreshToken) : null,
                    null
                );
            }
        }

        return Result::ok([
            'message' => lang('Users.logout.success'),
        ]);
    }

    private function validateRequired(array $data, array $fields): array
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function findUserById(?string $id): Result
    {
        $model = new UserModel();
        $user = $model->getUserById((string) $id);

        if (!$user) {
            return Result::notFound(lang('Users.errors.not_found'));
        }

        return Result::ok($user);
    }

    private function updateUserRecord(
        string $id,
        array $input,
        RequestInterface $request,
        bool $isAdminUpdate,
        ?string $actorId = null
    ): Result {
        $model = new UserModel();
        $before = $model->getUserById($id);
        $data = $this->prepareUpdateData($input, $isAdminUpdate);

        $missing = $this->validateRequired($data, ['email', 'first_name', 'last_name']);
        if (!empty($missing)) {
            return Result::fail([
                'error' => lang('Users.errors.required_fields'),
                'missing' => $missing,
            ], 400);
        }

        $rules = $model->getStrictUpdateRules();
        $rules['email'] = "required|valid_email|is_unique[users.email,id,{$id}]";
        $model->setValidationRules($rules);

        $updated = $isAdminUpdate ? $model->update($id, $data) : $model->updateProfile($id, $data);
        if (!$updated) {
            return Result::fail($model->errors(), 400);
        }

        $user = $model->getUserById($id);

        if ($isAdminUpdate) {
            (new AdminHistory())->logUserUpdate($request, $actorId, $id, $before, $user);

            return Result::ok([
                'message' => lang('Users.admin.updated'),
                'user' => $user,
            ]);
        }

        (new UserHistory())->logProfileUpdate($request, $id, $before, $user);

        return Result::ok([
            'message' => lang('Users.profile.updated'),
            'user' => $user,
        ]);
    }

    private function prepareUpdateData(array $input, bool $includeAdminFields): array
    {
        $data = [
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
        ];

        if ($includeAdminFields) {
            $data['role'] = $input['role'] ?? null;
            $data['is_active'] = $this->normalizeActiveValue($input['is_active'] ?? null);
        }

        if (array_key_exists('password', $input)) {
            $data['password'] = $input['password'];
        }

        return array_filter($data, static fn($value) => $value !== null);
    }

    private function normalizeActiveValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return $value ? '1' : '0';
        }

        return $value;
    }

    private function deleteUserRecord(
        string $id,
        callable $onDeleted,
        string $errorMessageKey,
        string $successMessageKey
    ): Result {
        $model = new UserModel();
        $before = $model->getUserById($id);

        if (!$model->delete($id)) {
            return Result::fail(lang($errorMessageKey), 500);
        }

        $onDeleted($before);

        return Result::ok([
            'message' => lang($successMessageKey),
        ]);
    }

    private function issueTokens(array $user, RequestInterface $request): array
    {
        $jwt = new JWTLibrary();

        return [
            'token' => $jwt->encode($this->buildTokenPayload($user)),
            'refresh_token' => $this->issueRefreshToken($user['id'], $request),
        ];
    }

    private function buildTokenPayload(array $user): array
    {
        return [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'scopes' => $this->getScopesForRole($user['role']),
        ];
    }

    private function issueRefreshToken(string $userId, RequestInterface $request): string
    {
        $model = new RefreshTokenModel();
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = time() + (int) (getenv('JWT_REFRESH_TTL') ?: 60 * 60 * 24 * 7);
        $id = $this->uuidV4();

        // Handle CLIRequest which may not have all methods
        $ipAddress = method_exists($request, 'getIPAddress') ? $request->getIPAddress() : '127.0.0.1';
        $userAgent = method_exists($request, 'getUserAgent') ? substr((string) $request->getUserAgent(), 0, 255) : 'CLI';

        $model->insert([
            'id' => $id,
            'user_id' => $userId,
            'token_hash' => $hash,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'revoked_at' => null,
            'replaced_by' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return $token;
    }

    private function getRefreshTokenId(string $refreshToken): ?string
    {
        $model = new RefreshTokenModel();
        $hash = hash('sha256', $refreshToken);
        $record = $model->where('token_hash', $hash)->first();

        return $record['id'] ?? null;
    }

    private function getScopesForRole(string $role): array
    {
        if ($role === 'admin') {
            return ['users:read', 'users:write', 'admin:all'];
        }

        if ($role === 'worker') {
            return ['users:read', 'atelier:read', 'atelier:write'];
        }

        return ['users:read', 'users:write'];
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}

