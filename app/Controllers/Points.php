<?php

namespace App\Controllers;

use App\Application\Fidelite\PointFideliteService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Points extends ResourceController
{
    protected $format = 'json';

    public function mine(): ResponseInterface
    {
        try {
            $userId = $this->request->user['id'] ?? null;
            if (!$userId) return $this->failUnauthorized('Authentification requise.');
            $result = (new PointFideliteService())->history($userId);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Points mine error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }
}