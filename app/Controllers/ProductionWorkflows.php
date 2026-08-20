<?php

namespace App\Controllers;

use App\Application\Production\Workflow\ProductionWorkflowService;
use App\Application\Shared\Result;
use App\Application\Security\AdminAuthorizationService;
use CodeIgniter\RESTful\ResourceController;

class ProductionWorkflows extends ResourceController
{
    protected $format = 'json';

    private ?ProductionWorkflowService $service = null;

    private function service(): ProductionWorkflowService
    {
        if ($this->service === null) {
            $this->service = service('productionWorkflowService');
        }

        return $this->service;
    }

    public function index()
    {
        $projectId = $this->request->getGet('project_id');
        $workflowType = $this->request->getGet('workflow_type') ?? $this->request->getGet('type');
        $clientName = $this->request->getGet('client_name') ?? $this->request->getGet('client');
        $result = $this->service()->index($projectId, $workflowType, $clientName);

        return $this->respond($result->getPayload(), $result->getStatus());
    }

    public function show($id = null)
    {
        if (!$id) {
            return $this->fail('ID requis', 400);
        }

        $result = $this->service()->getById($id);
        return $this->respondResult($result);
    }

    public function create()
    {
        $auth = $this->request->user ?? null;
        $adminCheck = (new AdminAuthorizationService())->authorize($auth);
        if ($adminCheck->getType() !== Result::TYPE_OK) {
            return $this->respond($adminCheck->getPayload(), $adminCheck->getStatus());
        }

        $result = $this->service()->create($this->getInputData(), $auth);
        return $this->respondResult($result, true);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->fail('ID requis', 400);
        }

        $auth = $this->request->user ?? null;
        $adminCheck = (new AdminAuthorizationService())->authorize($auth);
        if ($adminCheck->getType() !== Result::TYPE_OK) {
            return $this->respond($adminCheck->getPayload(), $adminCheck->getStatus());
        }

        $result = $this->service()->update($id, $this->getInputData(), $auth);
        return $this->respondResult($result);
    }

    public function transition($id = null)
    {
        if (!$id) {
            return $this->fail('ID requis', 400);
        }

        $result = $this->service()->transition($id, $this->getInputData(), $this->request->user ?? null);
        return $this->respondResult($result);
    }

    public function kanban()
    {
        $result = (new \App\Application\Production\Kanban\KanbanService())->board();
        return $this->respond($result->getPayload(), $result->getStatus());
    }

    private function respondResult(Result $result, bool $created = false)
    {
        if (!in_array($result->getType(), [Result::TYPE_OK, Result::TYPE_CREATED], true)) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }

        if ($created) {
            return $this->respondCreated($result->getPayload());
        }

        if ($result->getType() === Result::TYPE_CREATED) {
            return $this->respondCreated($result->getPayload());
        }

        return $this->respond($result->getPayload(), $result->getStatus());
    }

    private function getInputData(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        $post = $this->request->getPost();

        return is_array($post) ? $post : [];
    }
}
