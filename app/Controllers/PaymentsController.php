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
            if (!$quoteId) return $this->failValidationErrors(['error' => 'quote_id requis.']);
            if (!$this->ownsQuote((string)$quoteId)) {
                return $this->failForbidden();
            }
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
            if (!$result->isSuccess()) {
                return $this->respond($result->getPayload(), $result->getStatus());
            }
            $payment = $result->getPayload();
            if (!$this->ownsQuote((string)($payment['quote_id'] ?? ''))) {
                return $this->failForbidden();
            }
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'PaymentsController show: ' . $e->getMessage());
            return $this->failServerError();
        }
    }

    private function ownsQuote(string $quoteId): bool
    {
        $actor = $this->request->user ?? [];
        if (($actor['role'] ?? null) === 'admin' || ($actor['role'] ?? null) === 'worker') {
            return true;
        }
        $quote = (new \App\Models\QuoteModel())->find($quoteId);
        if (!$quote) return false;
        if (!empty($quote['client_id']) && (string)$quote['client_id'] === (string)($actor['id'] ?? '')) {
            return true;
        }
        return !empty($quote['email'])
            && !empty($actor['email'])
            && strtolower((string)$quote['email']) === strtolower((string)$actor['email']);
    }

    public function submitForQuote($quoteId = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            $quoteId = (string)$quoteId;

            $quoteModel = new \App\Models\QuoteModel();
            $quote = $quoteModel->find($quoteId);
            if (!$quote) return $this->failNotFound('Devis introuvable.');

            $isAdmin = ($actor['role'] ?? null) === 'admin';
            if (!$isAdmin) {
                $ownQuote = $quote['client_id'] !== null
                    ? ($quote['client_id'] === ($actor['id'] ?? null))
                    : strtolower((string)($quote['email'] ?? '')) === strtolower((string)($actor['email'] ?? ''));
                if (!$ownQuote) {
                    return $this->failForbidden('Vous ne pouvez pas soumettre une preuve pour ce devis.');
                }
            }

            $paymentType = trim((string)($this->request->getPost('payment_type') ?? ''));
            $transactionRef = trim((string)($this->request->getPost('transaction_ref') ?? ''));
            if (!$paymentType) return $this->failValidationErrors(['error' => 'La méthode de paiement est requise.']);
            if (strlen($transactionRef) < 5) return $this->failValidationErrors(['error' => 'La référence de la transaction doit comporter au moins 5 caractères.']);

            $file = $this->request->getFile('proof_of_payment');
            $proofPath = null;
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
                if ($file->getSize() > 10 * 1024 * 1024 || !in_array($file->getMimeType(), $allowed, true)) {
                    return $this->failValidationErrors(['error' => 'Fichier non autorisé (PDF, PNG, JPG, WEBP, max 10 Mo).']);
                }
                $newName = $file->getRandomName();
                $file->move(WRITEPATH . 'uploads', $newName);
                $proofPath = 'uploads/' . $newName;
            }

            $result = $this->svc()->submitForQuote($quoteId, [
                'payment_type' => $paymentType,
                'transaction_ref' => $transactionRef,
                'proof_path' => $proofPath,
            ], $actor['id'] ?? null);

            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'PaymentsController submitForQuote: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
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
            $reviewerId = $actor['id'] ?? null;
            if (!$status) return $this->failValidationErrors(['error' => 'status requis.']);
            $result = $this->svc()->updateStatus((string)$id, $status, $reviewNote, $reviewerId);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (\Throwable $e) {
            log_message('error', 'PaymentsController updateStatus: ' . $e->getMessage());
            return $this->failServerError();
        }
    }
}
