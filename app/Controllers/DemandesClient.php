<?php

namespace App\Controllers;

use App\Application\DemandesClient\DemandeClientService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class DemandesClient extends ResourceController
{
    protected $format = 'json';

    private function svc(): DemandeClientService
    {
        return new DemandeClientService();
    }

    public function index(): ResponseInterface
    {
        try {
            $result = $this->svc()->list();
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'DemandesClient index: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function pendingCount(): ResponseInterface
    {
        try {
            $result = $this->svc()->pendingCount();
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'DemandesClient pendingCount: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $result = $this->svc()->getById((string)$id);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'DemandesClient show: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $input = $this->getInputData();
            $result = $this->svc()->create($input);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'DemandesClient create: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function update($id = null): ResponseInterface
    {
        try {
            $input = $this->getInputData();
            $result = $this->svc()->update((string)$id, $input);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'DemandesClient update: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function refuse($id = null): ResponseInterface
    {
        try {
            $result = $this->svc()->refuse((string)$id);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'DemandesClient refuse: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    private function getInputData(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) return $json;
        } catch (\Throwable) {}
        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }
}
