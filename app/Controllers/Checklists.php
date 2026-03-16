<?php

namespace App\Controllers;

use App\Application\Production\Checklist\ChecklistService;
use App\Application\Shared\Result;
use CodeIgniter\RESTful\ResourceController;

class Checklists extends ResourceController
{
    protected $format = 'json';
    private ?ChecklistService $service = null;

    private function service(): ChecklistService
    {
        if ($this->service === null) {
            $this->service = new ChecklistService();
        }
        return $this->service;
    }

    public function create()
    {
        $type = $this->request->getPost('type');
        $projectId = $this->request->getPost('project_id');

        if (!$type) {
            return $this->fail('Le type est requis', 400);
        }

        $result = $this->service()->create($type, $projectId);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }

        return $this->respondCreated($result->getPayload());
    }

    public function initialize()
    {
        $projectId = $this->request->getPost('project_id');

        if (!$projectId) {
            return $this->fail('Project ID requis', 400);
        }

        $result = $this->service()->initializeForProject($projectId);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }

        return $this->respondCreated($result->getPayload());
    }

    public function initializeCommand()
    {
        $projectId = $this->request->getPost('project_id');

        if (!$projectId) {
            return $this->fail('Project ID requis', 400);
        }

        $result = $this->service()->initializeCommandForProject($projectId);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }

        return $this->respondCreated($result->getPayload());
    }

    public function initializeDelivery()
    {
        $projectId = $this->request->getPost('project_id');

        if (!$projectId) {
            return $this->fail('Project ID requis', 400);
        }

        $result = $this->service()->initializeDeliveryForProject($projectId);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }

        return $this->respondCreated($result->getPayload());
    }

    public function show($id = null)
    {
        if (!$id) {
            return $this->fail('ID requis', 400);
        }

        $result = $this->service()->getById($id);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }

        return $this->respond($result->getPayload());
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->fail('ID requis', 400);
        }

        $itemIndex = $this->request->getRawInput()['item_index'] ?? null;
        $checked = $this->request->getRawInput()['checked'] ?? null;
        $value = $this->request->getRawInput()['value'] ?? null;

        if ($itemIndex === null || $checked === null) {
            return $this->fail('item_index et checked sont requis', 400);
        }

        $result = $this->service()->updateItem($id, (int)$itemIndex, (bool)$checked, $value);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }

        return $this->respond($result->getPayload());
    }

    public function removeValue($id = null)
    {
        if (!$id) {
            return $this->fail('ID requis', 400);
        }

        $itemIndex = $this->request->getRawInput()['item_index'] ?? null;
        $value = $this->request->getRawInput()['value'] ?? null;

        if ($itemIndex === null || $value === null) {
            return $this->fail('item_index et value sont requis', 400);
        }

        $result = $this->service()->removeItemValue($id, (int)$itemIndex, $value);
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED])) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }

        return $this->respond($result->getPayload());
    }

    public function project($projectId = null)
    {
        if (!$projectId) {
            return $this->fail('Project ID requis', 400);
        }

        $result = $this->service()->getByProject($projectId);
        return $this->respond($result->getPayload());
    }
}
