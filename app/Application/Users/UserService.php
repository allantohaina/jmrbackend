<?php

namespace App\Application\Users;

use App\Application\Shared\Result;
use App\History\AdminHistory;
use App\History\TokenHistory;
use App\History\UserHistory;
use App\Libraries\JWTLibrary;
use App\Models\RefreshTokenModel;
use App\Models\TokenBlacklistModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\IncomingRequest;

class UserService
{
    public function register(array $input, IncomingRequest $request): Result
    {
        $model = new UserModel();
        $jwt = new JWTLibrary();

        $data = [
            'email' => $input['email'] ?? null,
            'password' => $input['password'] ?? null,
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'role' => 'user',
        ];

        $missing = $this->validateRequired($data, ['email', 'password', 'first_name', 'last_name']);
        if (!empty($missing)) {
            return Result::fail([
                'error' => lang('Users.errors.required_fields'),
                'missing' => $missing,
            ], 400);
        }

        if (!$model->insert($data)) {
            return Result::fail($model->errors(), 400);
        }

        $userId = $model->getInsertID();
        $user = $model->getUserById($userId);

        $token = $jwt->encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'scopes' => $this->getScopesForRole($user['role']),
        ]);

        $refreshToken = $this->issueRefreshToken($user['id'], $request);

        (new UserHistory())->logRegister($request, $user);

        return Result::created([
            'message' => lang('Users.register.success'),
            'user' => $user,
            'token' => $token,
            'refresh_token' => $refreshToken,
        ]);
    }

    public function login(array $input, IncomingRequest $request): Result
    {
        $model = new UserModel();
        $jwt = new JWTLibrary();

        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!$email || !$password) {
            return Result::fail(lang('Users.login.required'), 400);
        }

        $maxAttempts = (int) (getenv('LOGIN_MAX_ATTEMPTS') ?: 5);
        $lockMinutes = (int) (getenv('LOGIN_LOCK_MINUTES') ?: 15);

        $userRecord = $model->getUserForLogin($email);

        if (!$userRecord || !($userRecord['is_active'] ?? false)) {
            (new UserHistory())->logLoginFailed($request, $email, $userRecord['id'] ?? null, 'invalid_credentials');
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

        $token = $jwt->encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'scopes' => $this->getScopesForRole($user['role']),
        ]);

        $refreshToken = $this->issueRefreshToken($user['id'], $request);

        (new UserHistory())->logLogin($request, $user);

        return Result::ok([
            'message' => lang('Users.login.success'),
            'user' => $user,
            'token' => $token,
            'refresh_token' => $refreshToken,
        ]);
    }

    public function profile(?string $userId): Result
    {
        $model = new UserModel();
        $user = $model->getUserById((string) $userId);

        if (!$user) {
            return Result::notFound(lang('Users.errors.not_found'));
        }

        return Result::ok($user);
    }

    public function updateProfile(?string $userId, array $input, IncomingRequest $request): Result
    {
        $model = new UserModel();
        $before = $model->getUserById((string) $userId);

        $data = [
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
        ];

        if (array_key_exists('password', $input)) {
            $data['password'] = $input['password'];
        }

        $data = array_filter($data, fn($value) => $value !== null);

        $missing = $this->validateRequired($data, ['email', 'first_name', 'last_name']);
        if (!empty($missing)) {
            return Result::fail([
                'error' => lang('Users.errors.required_fields'),
                'missing' => $missing,
            ], 400);
        }

        $rules = $model->getStrictUpdateRules();
        $rules['email'] = "required|valid_email|is_unique[users.email,id,{$userId}]";
        $model->setValidationRules($rules);

        if (!$model->updateProfile((string) $userId, $data)) {
            return Result::fail($model->errors(), 400);
        }

        $user = $model->getUserById((string) $userId);

        (new UserHistory())->logProfileUpdate($request, (string) $userId, $before, $user);

        return Result::ok([
            'message' => lang('Users.profile.updated'),
            'user' => $user,
        ]);
    }

    public function deleteProfile(?string $userId, IncomingRequest $request): Result
    {
        $model = new UserModel();
        $before = $model->getUserById((string) $userId);

        if (!$model->delete((string) $userId)) {
            return Result::fail(lang('Users.errors.delete_account'), 500);
        }

        (new UserHistory())->logProfileDelete($request, (string) $userId, $before);

        return Result::ok([
            'message' => lang('Users.profile.deleted'),
        ]);
    }

    public function listUsers(): Result
    {
        $model = new UserModel();
        return Result::ok($model->getAllUsers());
    }

    public function getUser(?string $id): Result
    {
        $model = new UserModel();
        $user = $model->getUserById((string) $id);

        if (!$user) {
            return Result::notFound(lang('Users.errors.not_found'));
        }

        return Result::ok($user);
    }

    public function updateUser(?string $id, array $input, ?string $actorId, IncomingRequest $request): Result
    {
        $model = new UserModel();
        $before = $model->getUserById((string) $id);

        $data = [
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
            'role' => $input['role'] ?? null,
            'is_active' => $input['is_active'] ?? null,
        ];

        if (array_key_exists('is_active', $data)) {
            $value = $data['is_active'];
            if (is_bool($value)) {
                $data['is_active'] = $value ? 'true' : 'false';
            } elseif (is_int($value)) {
                $data['is_active'] = $value ? '1' : '0';
            }
        }

        if (array_key_exists('password', $input)) {
            $data['password'] = $input['password'];
        }

        $data = array_filter($data, fn($value) => $value !== null);

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

        if (!$model->update((string) $id, $data)) {
            return Result::fail($model->errors(), 400);
        }

        $user = $model->getUserById((string) $id);

        (new AdminHistory())->logUserUpdate($request, $actorId, (string) $id, $before, $user);

        return Result::ok([
            'message' => lang('Users.admin.updated'),
            'user' => $user,
        ]);
    }

    public function deleteUser(?string $id, ?string $actorId, IncomingRequest $request): Result
    {
        $model = new UserModel();
        $before = $model->getUserById((string) $id);

        if (!$model->delete((string) $id)) {
            return Result::fail(lang('Users.errors.delete_user'), 500);
        }

        (new AdminHistory())->logUserDelete($request, $actorId, (string) $id, $before);

        return Result::ok([
            'message' => lang('Users.admin.deleted'),
        ]);
    }

    public function refreshToken(?string $refreshToken, IncomingRequest $request): Result
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

        $jwt = new JWTLibrary();
        $token = $jwt->encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'scopes' => $this->getScopesForRole($user['role']),
        ]);

        $newRefresh = $this->issueRefreshToken($user['id'], $request);

        $model->update($record['id'], [
            'revoked_at' => date('Y-m-d H:i:s'),
            'replaced_by' => $this->getRefreshTokenId($newRefresh),
        ]);

        $decoded = $jwt->decode($token);
        $jti = $decoded->jti ?? null;
        (new TokenHistory())->log(
            $request,
            'refresh',
            $user['id'],
            $jti,
            $this->getRefreshTokenId($newRefresh),
            ['refresh_token_rotated' => true]
        );

        return Result::ok([
            'token' => $token,
            'refresh_token' => $newRefresh,
        ]);
    }

    public function logout(?string $refreshToken, ?string $authHeader, IncomingRequest $request): Result
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

    private function issueRefreshToken(string $userId, IncomingRequest $request): string
    {
        $model = new RefreshTokenModel();
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = time() + (int) (getenv('JWT_REFRESH_TTL') ?: 60 * 60 * 24 * 30);
        $id = $this->uuidV4();

        $model->insert([
            'id' => $id,
            'user_id' => $userId,
            'token_hash' => $hash,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'revoked_at' => null,
            'replaced_by' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'ip_address' => $request->getIPAddress(),
            'user_agent' => substr((string) $request->getUserAgent(), 0, 255),
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

