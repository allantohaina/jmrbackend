<?php

namespace App\Controllers;

use App\Application\History\HistoryService;
use CodeIgniter\RESTful\ResourceController;

class History extends ResourceController
{
    protected $format = 'json';

    private ?HistoryService $historyService = null;

    public function audit()
    {
        return $this->respond($this->historyService()->audit($this->request)->getPayload());
    }

    public function tokens()
    {
        return $this->respond($this->historyService()->tokens($this->request)->getPayload());
    }

    public function projects()
    {
        return $this->respond($this->historyService()->projects($this->request)->getPayload());
    }

    private function historyService(): HistoryService
    {
        if ($this->historyService === null) {
            $this->historyService = new HistoryService();
        }

        return $this->historyService;
    }
}

