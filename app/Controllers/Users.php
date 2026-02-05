<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\JWTLibrary;
use CodeIgniter\RESTful\ResourceController;

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
            'role' => $user['role']
        ]);

        return $this->respond([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * Login user
     * POST /api/users/login
     */

    public function login()
    {
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
            'role' => $user['role']
        ]);

        return $this->respond([
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Get current user profile
     * GET /api/users/profile
     */

    public function profile()
    {
        $model = new UserModel();
        $userId = $this->request->user['id'] ?? null;

        $user = $model->getUserById($userId);

        if (!$user) {
            return $this->failNotFound('Utilisateur non trouvé');
        }

        return $this->respond($user);
    }

    /**
     * Update current user profile
     * PUT /api/users/profile
     */

    public function updateProfile()
    {
        $model = new UserModel();
        $userId = $this->request->user['id'] ?? null;
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

        return $this->respond([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user
        ]);
    }

    /**
     * Delete current user account
     * DELETE /api/users/profile
     */
    public function deleteProfile()
    {
        $model = new UserModel();
        $userId = $this->request->user['id'] ?? null;

        if (!$model->delete($userId)) {
            return $this->fail('Erreur lors de la suppression du compte', 500);
        }

        return $this->respond([
            'message' => 'Compte supprimé avec succès',
        ]);
    }

    /**
     * Get all users (Admin only)
     * GET /api/users
     */
    public function index()
    {
        $model = new UserModel();
        $users = $model->getAllUsers();

        return $this->respond($users);
    }

    /**
     * Get user by ID (Admin only)
     * GET /api/users/{id}
     */
    public function show($id = null)
    {
        $model = new UserModel();
        $user = $model->getUserById($id);

        if (!$user) {
            return $this->failNotFound('Utilisateur non trouvé');
        }

        return $this->respond($user);
    }

    /**
     * Update user by ID (Admin only)
     * PUT /api/users/{id}
     */

    public function update($id = null)
    {
        $model = new UserModel();
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

        return $this->respond([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user
        ]);
    }

    /**
     * Delete user by ID (Admin only)
     * DELETE /api/users/{id}
     */
    public function delete($id = null)
    {
        $model = new UserModel();

        if (!$model->delete($id)) {
            return $this->fail('Erreur lors de la suppression de l\'utilisateur', 500);
        }

        return $this->respond([
            'message' => 'Utilisateur supprimé avec succès',
        ]);
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
}
