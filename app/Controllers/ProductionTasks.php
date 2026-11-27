<?php

namespace App\Controllers;

use App\Application\Production\ProductionTaskService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class ProductionTasks extends ResourceController
{
    protected $format = 'json';

    private function svc(): ProductionTaskService
    {
        return new ProductionTaskService();
    }

    private function input(): array
    {
        $data = $this->request->getJSON(true);
        return is_array($data) ? $data : (array)$this->request->getPost();
    }

    public function tickets(): ResponseInterface
    {
        try {
            $result = $this->svc()->tickets($this->request->getGet('quote_id'));
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function createTicket(): ResponseInterface
    {
        try {
            $input = $this->input();
            $input['created_by'] = $this->request->user['id'] ?? null;
            $result = $this->svc()->createTicket($input);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function tasks($ticketId = null): ResponseInterface
    {
        try {
            $result = $this->svc()->tasks((string)$ticketId);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function createTask($ticketId = null): ResponseInterface
    {
        try {
            $result = $this->svc()->createTask((string)$ticketId, $this->input());
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function assign($id = null): ResponseInterface
    {
        try {
            $input = $this->input();
            $result = $this->svc()->assignTask((string)$id, $input['assigned_worker_id'] ?? null);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function review($id = null): ResponseInterface
    {
        try {
            $result = $this->svc()->reviewTask(
                (string)$id,
                $this->input(),
                $this->request->user['id'] ?? null
            );
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function reviews($ticketId = null): ResponseInterface
    {
        try {
            $result = $this->svc()->reviews((string)$ticketId);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function close($ticketId = null): ResponseInterface
    {
        try {
            $result = $this->svc()->closeTicket((string)$ticketId);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
