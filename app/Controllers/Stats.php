<?php

namespace App\Controllers;

use App\Application\Stats\StatsService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Stats extends ResourceController
{
    protected $format = 'json';

    public function dashboard(): ResponseInterface
    {
        try {
            $result = (new StatsService())->dashboard();
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Stats dashboard error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }
}