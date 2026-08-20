<?php

namespace App\Controllers;

use App\Application\Avis\AvisService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Avis extends ResourceController
{
    protected $format = 'json';

    private ?AvisService $service = null;

    private function service(): AvisService
    {
        if ($this->service === null) {
            $this->service = new AvisService();
        }
        return $this->service;
    }

    public function publicList($produitId = null): ResponseInterface
    {
        try {
            $result = $this->service()->publicList((string)$produitId);
            if (!$result->isSuccess()) return $this->fail($result->getPayload(), $result->getStatus());
            $this->response->setHeader('Cache-Control', 'public, max-age=120');
            return $this->respond($result->getPayload());
        } catch (Throwable $e) {
            log_message('error', 'Avis publicList error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function submit($produitId = null): ResponseInterface
    {
        try {
            $userId = $this->request->user['id'] ?? null;
            $result = $this->service()->submit((string)$produitId, $this->getInputData(), $userId);
            if ($result->isSuccess()) return $this->respondCreated($result->getPayload());
            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Avis submit error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function index(): ResponseInterface
    {
        try {
            $statut = $this->request->getGet('statut') ?? null;
            $result = $this->service()->moderationList($statut);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Avis index error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function updateStatut($id = null): ResponseInterface
    {
        try {
            $input = $this->getInputData();
            $adminId = $this->request->user['id'] ?? null;
            $result = $this->service()->updateStatut((string)$id, (string)($input['statut'] ?? ''), $adminId);
            if ($result->isSuccess()) return $this->respond($result->getPayload());
            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Avis updateStatut error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    private function getInputData(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) return $json;
        } catch (Throwable) {
        }
        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) return $raw;
        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }
}