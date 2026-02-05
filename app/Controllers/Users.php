<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\JWTLibrary;
use App\Exceptions\ApiException;
use App\Exceptions\UnknownException;
use App\Models\RefreshTokenModel;
use App\Models\TokenBlacklistModel;
use App\History\UserHistory;
use App\History\AdminHistory;
use App\History\TokenHistory;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Users extends ResourceController
{
    protected $modelName = 'App\Models\UserModel';
    protected $format = 'json';

    /**
     * Register a new user
     * POST /api/users/register
     */

    public function register()
    {
        try {
            $model = new UserModel();
            $jwt = new JWTLibrary();

            $input = $this->getInputData();

            $data = [
                'email' => $input['email'] ?? null,
                'password' => $input['password'] ?? null,
                'first_name' => $input['first_name'] ?? null,
                'last_name' => $input['last_name'] ?? null,
                'phone' => $input['phone'] ?? null,
                'role' => 'user', // Default role
            ];

            $missing = $this->validateRequired($data, ['email', 'password', 'first_name', 'last_name']);
            if ($missing) {
                return $missing;
            }

            if (!$model->insert($data)) {
                return $this->fail($model->errors(), 400);
            }

            $userId = $model->getInsertID();
            $user = $model->getUserById($userId);

            // Generate JWT token
            $token = $jwt->encode([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'scopes' => $this->getScopesForRole($user['role']),
            ]);

            $refreshToken = $this->issueRefreshToken($user['id']);

            (new UserHistory())->logRegister($this->request, $user);

            return $this->respond([
                'message' => 'Utilisateur créé avec succès',
                'user' => $user,
                'token' => $token,
                'refresh_token' => $refreshToken
            ], 201);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Login user
     * POST /api/users/login
     */

    public function login()
    {
        try {
            $model = new UserModel();
            $jwt = new JWTLibrary();

            $input = $this->getInputData();
            $email = $input['email'] ?? null;
            $password = $input['password'] ?? null;

            if (!$email || !$password) {
                return $this->fail('Email et mot de passe requis', 400);
            }

            $user = $model->verifyCredentials($email, $password);

            if (!$user) {
                return $this->fail('Email ou mot de passe incorrect', 401);
            }

            // Generate JWT token
            $token = $jwt->encode([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'scopes' => $this->getScopesForRole($user['role']),
            ]);

            $refreshToken = $this->issueRefreshToken($user['id']);

            (new UserHistory())->logLogin($this->request, $user);

            return $this->respond([
                'message' => 'Connexion réussie',
                'user' => $user,
                'token' => $token,
                'refresh_token' => $refreshToken
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get current user profile
     * GET /api/users/profile
     */

    public function profile()
    {
        try {
            $model = new UserModel();
            $userId = $this->request->user['id'] ?? null;

            $user = $model->getUserById($userId);

            if (!$user) {
                return $this->failNotFound('Utilisateur non trouvé');
            }

            return $this->respond($user);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update current user profile
     * PUT /api/users/profile
     */

    public function updateProfile()
    {
        try {
            $model = new UserModel();
            $userId = $this->request->user['id'] ?? null;
            $before = $model->getUserById($userId);
            $input = $this->getInputData();

            $data = [
                'first_name' => $input['first_name'] ?? null,
                'last_name' => $input['last_name'] ?? null,
                'phone' => $input['phone'] ?? null,
                'email' => $input['email'] ?? null,
            ];

            // Remove password from update if provided (use separate endpoint)
            if (array_key_exists('password', $input)) {
                $data['password'] = $input['password'];
            }

            // Filter out null values
            $data = array_filter($data, fn($value) => $value !== null);

            $missing = $this->validateRequired($data, ['email', 'first_name', 'last_name']);
            if ($missing) {
                return $missing;
            }

            $rules = $model->getStrictUpdateRules();
            $rules['email'] = "required|valid_email|is_unique[users.email,id,{$userId}]";
            $model->setValidationRules($rules);

            if (!$model->updateProfile($userId, $data)) {
                return $this->fail($model->errors(), 400);
            }

            $user = $model->getUserById($userId);

            (new UserHistory())->logProfileUpdate($this->request, $userId, $before, $user);

            return $this->respond([
                'message' => 'Profil mis à jour avec succès',
                'user' => $user
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete current user account
     * DELETE /api/users/profile
     */
    public function deleteProfile()
    {
        try {
            $model = new UserModel();
            $userId = $this->request->user['id'] ?? null;
            $before = $model->getUserById($userId);

            if (!$model->delete($userId)) {
                return $this->fail('Erreur lors de la suppression du compte', 500);
            }

            (new UserHistory())->logProfileDelete($this->request, $userId, $before);

            return $this->respond([
                'message' => 'Compte supprimé avec succès',
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get all users (Admin only)
     * GET /api/users
     */
    public function index()
    {
        try {
            $model = new UserModel();
            $users = $model->getAllUsers();

            return $this->respond($users);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get user by ID (Admin only)
     * GET /api/users/{id}
     */
    public function show($id = null)
    {
        try {
            $model = new UserModel();
            $user = $model->getUserById($id);

            if (!$user) {
                return $this->failNotFound('Utilisateur non trouvé');
            }

            return $this->respond($user);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update user by ID (Admin only)
     * PUT /api/users/{id}
     */

    public function update($id = null)
    {
        try {
            $model = new UserModel();
            $before = $model->getUserById($id);
            $input = $this->getInputData();

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

            // Filter out null values
            $data = array_filter($data, fn($value) => $value !== null);

            $missing = $this->validateRequired($data, ['email', 'first_name', 'last_name']);
            if ($missing) {
                return $missing;
            }

            $rules = $model->getStrictUpdateRules();
            $rules['email'] = "required|valid_email|is_unique[users.email,id,{$id}]";
            $model->setValidationRules($rules);

            if (!$model->update($id, $data)) {
                return $this->fail($model->errors(), 400);
            }

            $user = $model->getUserById($id);

            (new AdminHistory())->logUserUpdate($this->request, $this->request->user['id'] ?? null, $id, $before, $user);

            return $this->respond([
                'message' => 'Utilisateur mis à jour avec succès',
                'user' => $user
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete user by ID (Admin only)
     * DELETE /api/users/{id}
     */
    public function delete($id = null)
    {
        try {
            $model = new UserModel();
            $before = $model->getUserById($id);

            if (!$model->delete($id)) {
                return $this->fail('Erreur lors de la suppression de l\'utilisateur', 500);
            }

            (new AdminHistory())->logUserDelete($this->request, $this->request->user['id'] ?? null, $id, $before);

            return $this->respond([
                'message' => 'Utilisateur supprimé avec succès',
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Refresh access token
     * POST /api/users/refresh
     */
    public function refresh()
    {
        try {
            $input = $this->getInputData();
            $refreshToken = $input['refresh_token'] ?? null;

            if (!$refreshToken) {
                return $this->fail('Refresh token requis', 400);
            }

            $model = new RefreshTokenModel();
            $hash = hash('sha256', $refreshToken);
            $record = $model->where('token_hash', $hash)->first();

            if (!$record || $record['revoked_at'] !== null) {
                return $this->fail('Refresh token invalide', 401);
            }

            if (strtotime($record['expires_at']) < time()) {
                return $this->fail('Refresh token expiré', 401);
            }

            $userModel = new UserModel();
            $user = $userModel->getUserById($record['user_id']);
            if (!$user) {
                return $this->failNotFound('Utilisateur non trouvé');
            }

            $jwt = new JWTLibrary();
            $token = $jwt->encode([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'scopes' => $this->getScopesForRole($user['role']),
            ]);

            $newRefresh = $this->issueRefreshToken($user['id']);

            $model->update($record['id'], [
                'revoked_at' => date('Y-m-d H:i:s'),
                'replaced_by' => $this->getRefreshTokenId($newRefresh),
            ]);

            $decoded = $jwt->decode($token);
            $jti = $decoded->jti ?? null;
            (new TokenHistory())->log(
                $this->request,
                'refresh',
                $user['id'],
                $jti,
                $this->getRefreshTokenId($newRefresh),
                ['refresh_token_rotated' => true]
            );

            return $this->respond([
                'token' => $token,
                'refresh_token' => $newRefresh,
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Logout: revoke refresh token and blacklist access token
     * POST /api/users/logout
     */
    public function logout()
    {
        try {
            $input = $this->getInputData();
            $refreshToken = $input['refresh_token'] ?? null;

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

            $authHeader = $this->request->getHeaderLine('Authorization');
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
                        $this->request,
                        'logout',
                        $decoded->user_id ?? null,
                        $decoded->jti,
                        $refreshToken ? $this->getRefreshTokenId($refreshToken) : null,
                        null
                    );
                }
            }

            return $this->respond([
                'message' => 'Déconnexion réussie',
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    private function getInputData(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }

    private function validateRequired(array $data, array $fields)
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            return $this->fail([
                'error' => 'Champs requis manquants',
                'missing' => $missing
            ], 400);
        }

        return null;
    }

    private function issueRefreshToken(string $userId): string
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
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
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

    private function handleException(Throwable $e)
    {
        if ($e instanceof ApiException) {
            return $this->respond([
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ], $e->getStatusCode());
        }

        $unknown = new UnknownException('Une erreur inattendue est survenue.', 0, $e);

        return $this->respond([
            'error' => $unknown->getMessage(),
        ], 500);
    }

}
