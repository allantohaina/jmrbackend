<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\AltchaVerify;
use App\Models\UserModel;

class AdminReset extends BaseController
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = getenv('ADMIN_RESET_SECRET') ?: 'jmr-reset-2026';
    }

    public function resetPassword(): ResponseInterface
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);

        if ($token !== $this->secretKey) {
            return $this->respond(['status' => 'error', 'message' => 'Clé secrète invalide.'], 403);
        }

        $data = $this->request->getJSON(true);

        $altchaResult = (new AltchaVerify())->verifyToken($data['altcha'] ?? null);
        if (!$altchaResult['verified']) {
            return $this->respond(['status' => 'error', 'message' => $altchaResult['error']], 403);
        }
        unset($data['altcha']);

        $email = $data['email'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$email || !$newPassword) {
            return $this->respond(['status' => 'error', 'message' => 'Email et mot de passe requis.'], 400);
        }

        if (strlen($newPassword) < 8) {
            return $this->respond(['status' => 'error', 'message' => 'Mot de passe trop court (minimum 8 caractères).'], 400);
        }

        $model = new UserModel();
        $user = $model->where('email', $email)->first();

        if (!$user) {
            return $this->respond(['status' => 'error', 'message' => 'Utilisateur non trouvé.'], 404);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $model->update($user['id'], ['password' => $passwordHash]);

        return $this->respond([
            'status' => 'success',
            'message' => "Mot de passe réinitialisé pour {$email}.",
        ]);
    }

    public function listUsers(): ResponseInterface
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);

        if ($token !== $this->secretKey) {
            return $this->respond(['status' => 'error', 'message' => 'Clé secrète invalide.'], 403);
        }

        $model = new UserModel();
        $users = $model->select('id, email, first_name, last_name, role, is_active, created_at')->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $users,
        ]);
    }
}
