<?php

namespace App\Controllers;

use App\Application\Payments\PaymentService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class PaymentsController extends ResourceController
{
    protected $format = 'json';
    private ?PaymentService $svc = null;

    private function svc(): PaymentService
    {
        if ($this->svc === null) {
            $this->svc = service('paymentService');
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
            log_message('error', 'PaymentsController index: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $result = $this->svc()->getById((string)$id);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'PaymentsController show: ' . $e->getMessage());
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
            $reviewNote = $data['review_note'] ?? null;
            $userName = trim(($actor['first_name'] ?? '') . ' ' . ($actor['last_name'] ?? '')) ?: 'Admin';
            if (!$status) return $this->failValidation('status requis.');
            $result = $this->svc()->updateStatus((string)$id, $status, $reviewNote, $userName);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'PaymentsController updateStatus: ' . $e->getMessage());
            return $this->failServerError();
        }
    }
}
