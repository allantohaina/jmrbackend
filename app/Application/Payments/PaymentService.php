<?php

namespace App\Application\Payments;

use App\Application\Shared\Result;
use App\Models\PaymentModel;

class PaymentService
{
    private PaymentModel $model;

    public function __construct(?PaymentModel $model = null)
    {
        $this->model = $model ?? new PaymentModel();
    }

    public function listByQuote(string $quoteId): Result
    {
        $rows = $this->model->where('quote_id', $quoteId)->orderBy('created_at', 'ASC')->findAll();
        $total = 0;
        foreach ($rows as $row) {
            if (($row['status'] ?? null) === 'verified') {
                $total += (float)($row['amount'] ?? 0);
            }
        }
        return Result::ok(['data' => $rows, 'total_verified' => $total]);
    }

    public function getById(string $id): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Paiement introuvable.');
        return Result::ok(['data' => $row]);
    }

    public function submitForQuote(string $quoteId, array $data, ?string $actorId = null): Result
    {
        $quoteModel = new \App\Models\QuoteModel();
        $quote = $quoteModel->find($quoteId);
        if (!$quote) return Result::notFound('Devis introuvable.');

        $paymentModel = $this->model;
        $payment = $paymentModel->where('quote_id', $quoteId)
            ->where('status', 'submitted')
            ->orderBy('created_at', 'ASC')
            ->first();
        if (!$payment) {
            return Result::fail(['error' => 'Aucune tranche de paiement en attente pour ce devis.'], 422);
        }

        $updateData = [
            'proof_path' => $data['proof_path'] ?? null,
            'submitted_by' => $actorId,
        ];
        if (!empty($data['payment_type'])) $updateData['payment_type'] = $data['payment_type'];
        if (!empty($data['transaction_ref'])) $updateData['transaction_ref'] = $data['transaction_ref'];

        if (!$paymentModel->update($payment['id'], $updateData)) {
            return Result::fail(['error' => 'Erreur lors de l\'enregistrement de la preuve.', 'messages' => $paymentModel->errors()], 422);
        }

        return Result::ok(['data' => $paymentModel->find($payment['id'])]);
    }

    public function updateStatus(string $id, string $status, ?string $reviewNote = null, ?string $reviewedBy = null): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Paiement introuvable.');

        $updateData = [
            'status' => $status,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];
        if ($reviewNote !== null) $updateData['review_note'] = $reviewNote;
        if ($reviewedBy !== null) $updateData['reviewed_by'] = $reviewedBy;

        if (!$this->model->update($id, $updateData)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour.'], 422);
        }

        // If verified, update the quote's deposit_paid or balance_paid
        if ($status === 'verified' && !empty($row['quote_id'])) {
            $this->markQuotePaymentAsPaid($row['quote_id'], $row['phase']);
        }

        return Result::ok(['data' => $this->model->find($id)]);
    }

    private function markQuotePaymentAsPaid(string $quoteId, string $phase): void
    {
        $quoteModel = new \App\Models\QuoteModel();
        $update = [];
        if ($phase === 'deposit') {
            $update['deposit_paid'] = true;
            $update['deposit_paid_at'] = date('Y-m-d H:i:s');
        } elseif ($phase === 'balance') {
            $update['balance_paid'] = true;
            $update['balance_paid_at'] = date('Y-m-d H:i:s');
        }
        if (!empty($update)) {
            $quoteModel->update($quoteId, $update);
        }

        // Auto-create tranche 2 (balance) when tranche 1 (deposit) is verified
        if ($phase === 'deposit') {
            $this->createTranche2IfMissing($quoteId);
        }
    }

    private function createTranche2IfMissing(string $quoteId): void
    {
        $existing = $this->model->where('quote_id', $quoteId)
            ->where('phase', 'balance')
            ->first();
        if ($existing) return;

        $quoteModel = new \App\Models\QuoteModel();
        $quote = $quoteModel->find($quoteId);
        if (!$quote) return;

        $depositAmount = (float)($quote['deposit_amount'] ?? 0);
        $totalAmount = (float)($quote['amount'] ?? 0);
        $balanceAmount = (float)($quote['balance_amount'] ?? 0);

        // Balance = total - deposit + any addon additions to balance_amount
        $balance = $balanceAmount > 0 ? $balanceAmount : ($totalAmount - $depositAmount);
        if ($balance <= 0) return;

        $this->model->insert([
            'quote_id' => $quoteId,
            'phase' => 'balance',
            'amount' => $balance,
            'status' => 'submitted',
        ]);

        $quoteModel->update($quoteId, [
            'balance_amount' => $balance,
            'balance_paid' => false,
        ]);
    }

    public function syncTranche2Amount(string $quoteId): void
    {
        $quoteModel = new \App\Models\QuoteModel();
        $quote = $quoteModel->find($quoteId);
        if (!$quote) return;

        $totalAmount = (float)($quote['amount'] ?? 0);
        $depositAmount = (float)($quote['deposit_amount'] ?? 0);
        $balanceAmount = (float)($quote['balance_amount'] ?? 0);

        $newBalance = $balanceAmount > 0 ? $balanceAmount : ($totalAmount - $depositAmount);

        $existing = $this->model->where('quote_id', $quoteId)
            ->where('phase', 'balance')
            ->first();

        if ($existing && (float)($existing['amount'] ?? 0) !== $newBalance && $existing['status'] !== 'verified') {
            $this->model->update($existing['id'], ['amount' => $newBalance]);
        }
    }
}
