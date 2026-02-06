<?php

namespace App\Application\Security;

use App\Application\Shared\Result;
use App\Libraries\JWTLibrary;
use App\Models\TokenBlacklistModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;

class AuthContextService
{
    public function authenticate(RequestInterface $request): Result
    {
        $jwt = new JWTLibrary();

        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return Result::unauthorized(['error' => 'Token manquant']);
        }

        $token = $matches[1];
        $decoded = $jwt->decode($token);

        if (!$decoded) {
            return Result::unauthorized(['error' => 'Token invalide ou expirÃ©']);
        }

        if (!isset($decoded->jti)) {
            return Result::unauthorized(['error' => 'Token invalide']);
        }

        $blacklist = new TokenBlacklistModel();
        $revoked = $blacklist->where('jti', $decoded->jti)->first();
        if ($revoked) {
            return Result::unauthorized(['error' => 'Token rÃ©voquÃ©']);
        }

        $model = new UserModel();
        $user = $model->getUserById($decoded->user_id ?? '');

        if (!$user || empty($user['is_active'])) {
            return Result::unauthorized(['error' => 'Utilisateur inactif ou introuvable']);
        }

        return Result::ok($user);
    }
}

