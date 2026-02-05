<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTLibrary;
use App\Models\UserModel;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwt = new JWTLibrary();
        
        // Get token from Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return service('response')
                ->setJSON(['error' => 'Token manquant'])
                ->setStatusCode(401);
        }

        $token = $matches[1];
        $decoded = $jwt->decode($token);

        if (!$decoded) {
            return service('response')
                ->setJSON(['error' => 'Token invalide ou expiré'])
                ->setStatusCode(401);
        }

        $model = new UserModel();
        $user = $model->getUserById($decoded->user_id ?? '');

        if (!$user || empty($user['is_active'])) {
            return service('response')
                ->setJSON(['error' => 'Utilisateur inactif ou introuvable'])
                ->setStatusCode(401);
        }

        // Store user data in request for later use
        $request->user = $user;
        
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
