<?php

namespace App\Controllers;

use App\Application\MatierePremiere\MatierePremiereService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Matieres extends ResourceController
{
    protected $format = 'json';

    private ?MatierePremiereService $service = null;

    private function service(): MatierePremiereService
    {
        if ($this->service === null) {
            $this->service = new MatierePremiereService();
        }
        return $this->service;
    }

    public function index(): ResponseInterface
    {
        try {
            $result = $this->service()->list();
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Matieres index error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function alertes(): ResponseInterface
    {
        try {
            $result = $this->service()->alertes();
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Matieres alertes error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $result = $this->service()->getById((string)$id);
            if (!$result->isSuccess()) return $this->fail($result->getPayload(), $result->getStatus());
            return $this->respond($result->getPayload());
        } catch (Throwable $e) {
            log_message('error', 'Matieres show error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $result = $this->service()->create($this->getInputData());
            if ($result->isSuccess()) return $this->respondCreated($result->getPayload());
            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Matieres create error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function update($id = null): ResponseInterface
    {
        try {
            $result = $this->service()->update((string)$id, $this->getInputData());
            if ($result->isSuccess()) return $this->respond($result->getPayload());
            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Matieres update error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function delete($id = null): ResponseInterface
    {
        try {
            $result = $this->service()->delete((string)$id);
            if ($result->isSuccess()) return $this->respond($result->getPayload());
            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Matieres delete error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function mouvement(): ResponseInterface
    {
        try {
            $actorId = $this->request->user['id'] ?? null;
            $result = $this->service()->mouvement($this->getInputData(), $actorId);
            if ($result->isSuccess()) return $this->respondCreated($result->getPayload());
            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Matieres mouvement error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    private function getInputData(): array
    {
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'multipart/form-data')) {
            $post = $this->request->getPost();
            return is_array($post) ? $post : [];
        }
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