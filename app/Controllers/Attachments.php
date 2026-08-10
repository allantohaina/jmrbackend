<?php

namespace App\Controllers;

use App\Application\Attachments\AttachmentService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class Attachments extends ResourceController
{
    protected $format = 'json';

    private function svc(): AttachmentService
    {
        return new AttachmentService();
    }

    public function index(): ResponseInterface
    {
        try {
            $entityType = (string)($this->request->getGet('entity_type') ?? '');
            $entityId = (string)($this->request->getGet('entity_id') ?? '');
            $result = $this->svc()->listByEntity($entityType, $entityId);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'Attachments index: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $result = $this->svc()->getById((string)$id);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'Attachments show: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $input = $this->getInputData();
            $actor = $this->request->user ?? [];
            if (!empty($actor['id'])) {
                $input['uploaded_by'] = $actor['id'];
            }
            $result = $this->svc()->create($input);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'Attachments create: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function delete($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            $isAdmin = ($actor['role'] ?? null) === 'admin';
            $result = $this->svc()->delete((string)$id, $isAdmin ? null : ($actor['id'] ?? null));
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'Attachments delete: ' . $e->getMessage());
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
