<?php

namespace App\Controllers;

use App\Application\QuoteAddons\QuoteAddonService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class QuoteAddons extends ResourceController
{
    protected $format = 'json';
    private ?QuoteAddonService $svc = null;

    private function svc(): QuoteAddonService
    {
        if ($this->svc === null) {
            $this->svc = service('quoteAddonService');
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
            log_message('error', 'QuoteAddons index: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $result = $this->svc()->getById((string)$id);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'QuoteAddons show: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            $data = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
            $result = $this->svc()->create(is_array($data) ? $data : []);
            if (!$result->isSuccess()) return $this->respond($result->getPayload(), $result->getStatus());
            return $this->respondCreated($result->getPayload());
        } catch (\Throwable $e) {
            log_message('error', 'QuoteAddons create: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function updateStatus($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') return $this->failForbidden();
            $data = $this->request->getJSON(true) ?? [];
            $status = $data['status'] ?? null;
            $price = isset($data['price']) ? (float)$data['price'] : null;
            if (!$status) return $this->failValidation('status requis.');
            $result = $this->svc()->updateStatus((string)$id, $status, $price);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'QuoteAddons updateStatus: ' . $e->getMessage());
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
            log_message('error', 'QuoteAddons delete: ' . $e->getMessage());
            return $this->failServerError();
        }
    }
}
