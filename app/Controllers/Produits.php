<?php

namespace App\Controllers;

use App\Application\Produits\ProduitService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class Produits extends ResourceController
{
    protected $format = 'json';
    private ?ProduitService $svc = null;

    private function svc(): ProduitService
    {
        if ($this->svc === null) {
            $this->svc = service('produitService');
        }
        return $this->svc;
    }

    public function index(): ResponseInterface
    {
        try {
            $result = $this->svc()->list();
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'Produits index: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function categories(): ResponseInterface
    {
        try {
            $result = $this->svc()->categories();
            return $this->respond($result->getPayload());
        } catch (\Throwable $e) {
            log_message('error', 'Produits categories: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $result = $this->svc()->getById((string)$id);
            if (!$result->isSuccess()) return $this->respond($result->getPayload(), $result->getStatus());
            return $this->respond($result->getPayload());
        } catch (\Throwable $e) {
            log_message('error', 'Produits show: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') return $this->failForbidden();
            $data = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
            $result = $this->svc()->create(is_array($data) ? $data : []);
            if (!$result->isSuccess()) return $this->respond($result->getPayload(), $result->getStatus());
            return $this->respondCreated($result->getPayload());
        } catch (\Throwable $e) {
            log_message('error', 'Produits create: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError();
        }
    }

    public function update($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') return $this->failForbidden();
            $data = $this->request->getJSON(true) ?? $this->request->getRawInput() ?? [];
            $result = $this->svc()->update((string)$id, is_array($data) ? $data : []);
            if (!$result->isSuccess()) return $this->respond($result->getPayload(), $result->getStatus());
            return $this->respond($result->getPayload());
        } catch (\Throwable $e) {
            log_message('error', 'Produits update: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function delete($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') return $this->failForbidden();
            $result = $this->svc()->delete((string)$id);
            if (!$result->isSuccess()) return $this->respond($result->getPayload(), $result->getStatus());
            return $this->respondDeleted($result->getPayload());
        } catch (\Throwable $e) {
            log_message('error', 'Produits delete: ' . $e->getMessage());
            return $this->failServerError();
        }
    }
}
