<?php

namespace App\Controllers;

use App\Application\Production\Assemblage\AssemblageService;
use App\Application\Shared\Result;
use CodeIgniter\RESTful\ResourceController;

class Assemblages extends ResourceController
{
    protected $format = 'json';
    private ?AssemblageService $service = null;

    private function service(): AssemblageService
    {
        if ($this->service === null) {
            $this->service = service('assemblageService');
        }
        return $this->service;
    }

    public function index()
    {
        $projectId = $this->request->getGet('project_id');
        if (!$projectId) {
            return $this->fail('L\'identifiant du projet est requis.', 400);
        }
        $result = $this->service()->getByProject($projectId);
        return $this->respond($result->getPayload());
    }

    public function show($id = null)
    {
        $result = $this->service()->getById($id);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respond($result->getPayload());
    }

    public function create()
    {
        $data = $this->request->getPost();
        $result = $this->service()->create($data);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respondCreated($result->getPayload());
    }

    public function update($id = null)
    {
        $data = $this->request->getRawInput();
        $result = $this->service()->update($id, $data);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respond($result->getPayload());
    }

    public function delete($id = null)
    {
        $result = $this->service()->delete($id);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respondDeleted($result->getPayload());
    }
}
