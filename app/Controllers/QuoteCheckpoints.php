<?php

namespace App\Controllers;

use App\Application\QuoteCheckpoints\QuoteCheckpointService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class QuoteCheckpoints extends ResourceController
{
    protected $format = 'json';
    private ?QuoteCheckpointService $svc = null;

    private function svc(): QuoteCheckpointService
    {
        if ($this->svc === null) {
            $this->svc = service('quoteCheckpointService');
        }
        return $this->svc;
    }

    public function index(): ResponseInterface
    {
        try {
            $quoteId = $this->request->getGet('quote_id');
            if (!$quoteId) return $this->failValidation('quote_id requis.');
            $result = $this->svc()->listByQuote((string)$quoteId);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'QuoteCheckpoints index: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $result = $this->svc()->getById((string)$id);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'QuoteCheckpoints show: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
            $result = $this->svc()->create(is_array($data) ? $data : []);
            if (!$result->isSuccess()) return $this->respond($result->getPayload(), $result->getStatus());
            return $this->respondCreated($result->getPayload());
        } catch (\Throwable $e) {
            log_message('error', 'QuoteCheckpoints create: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function validateCheckpoint($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            $userName = trim(($actor['first_name'] ?? '') . ' ' . ($actor['last_name'] ?? '')) ?: 'Client';
            $result = $this->svc()->validate((string)$id, $userName);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'QuoteCheckpoints validate: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function delete($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') return $this->failForbidden();
            $result = $this->svc()->delete((string)$id);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'QuoteCheckpoints delete: ' . $e->getMessage());
            return $this->failServerError();
        }
    }
}
