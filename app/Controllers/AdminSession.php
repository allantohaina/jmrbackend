<?php

namespace App\Controllers;

use App\Application\Shared\Result;
use App\Application\Users\UserService;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

/**
 * Sessions admin : déconnexion globale après mise à jour.
 */
class AdminSession extends ResourceController
{
    protected $format = 'json';

    public function logoutAll()
    {
        try {
            $result = (new UserService())->logoutAll();
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', '[AdminSession::logoutAll] {message}' . PHP_EOL . '{trace}', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->fail(['error' => 'Erreur interne.'], 500);
        }
    }
}
