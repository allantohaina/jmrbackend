<?php

namespace App\Controllers;

use App\Application\Commandes\CommandeService;
use App\Application\Shared\Result;
use CodeIgniter\RESTful\ResourceController;

class Commandes extends ResourceController
{
    protected $format = 'json';
    private ?CommandeService $service = null;

    private function service(): CommandeService
    {
        if ($this->service === null) {
            $this->service = service('commandeService');
        }
        return $this->service;
    }

    public function index()
    {
        $userId = $this->request->user['id'] ?? null;
        $result = $this->service()->list($userId);
        return $this->respond($result->getPayload());
    }

    public function show($id = null)
    {
        $result = $this->service()->getById($id);
        if (!$result->isSuccess()) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respond($result->getPayload());
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $result = $this->service()->create($data);
        if (!$result->isSuccess()) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respondCreated($result->getPayload());
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $result = $this->service()->update($id, $data);
        if (!$result->isSuccess()) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respond($result->getPayload());
    }

    public function delete($id = null)
    {
        $result = $this->service()->delete($id);
        if (!$result->isSuccess()) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respondDeleted($result->getPayload());
    }
}
