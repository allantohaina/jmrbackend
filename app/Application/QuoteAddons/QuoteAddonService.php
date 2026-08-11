<?php

namespace App\Application\QuoteAddons;

use App\Application\Shared\Result;
use App\Models\QuoteAddonModel;
use App\Models\QuoteModel;

class QuoteAddonService
{
    private QuoteAddonModel $model;
    private QuoteModel $quoteModel;

    public function __construct(?QuoteAddonModel $model = null, ?QuoteModel $quoteModel = null)
    {
        $this->model = $model ?? new QuoteAddonModel();
        $this->quoteModel = $quoteModel ?? new QuoteModel();
    }

    public function listByQuote(string $quoteId): Result
    {
        $rows = $this->model->where('quote_id', $quoteId)->orderBy('created_at', 'ASC')->findAll();
        $totalValidated = 0;
        foreach ($rows as $row) {
            if (($row['status'] ?? null) === 'included' && $row['price'] != null) {
                $totalValidated += (float)$row['price'];
            }
        }
        return Result::ok(['data' => $rows, 'total_validated' => $totalValidated]);
    }

    public function getById(string $id): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Addon introuvable.');
        return Result::ok(['data' => $row]);
    }

    public function create(array $data): Result
    {
        if (empty($data['quote_id']) || empty($data['title'])) {
            return Result::fail(['error' => 'quote_id et title sont requis.'], 422);
        }
        $data['status'] = 'pending';
        if (!$this->model->insert($data)) {
            return Result::fail(['error' => 'Erreur lors de la création.', 'messages' => $this->model->errors()], 500);
        }
        $id = $this->model->getInsertID();
        return Result::created(['data' => $this->model->find($id)]);
    }

    public function updateStatus(string $id, string $status, ?float $price = null): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Addon introuvable.');
        
        $updateData = ['status' => $status];
        if ($price !== null) {
            $updateData['price'] = $price;
        }
        
        if (!$this->model->update($id, $updateData)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour.'], 500);
        }

        // If addon is included, add its price to the quote's balance_amount and sync tranche 2
        if ($status === 'included' && $price !== null) {
            $quote = $this->quoteModel->find($row['quote_id']);
            if ($quote) {
                $currentBalance = (float)($quote['balance_amount'] ?? 0);
                $this->quoteModel->update($row['quote_id'], [
                    'balance_amount' => $currentBalance + $price,
                ]);

                // Sync tranche 2 payment amount with updated balance
                $paymentService = new \App\Application\Payments\PaymentService();
                $paymentService->syncTranche2Amount((string)$row['quote_id']);
            }
        }

        // If addon is rejected, subtract its price from balance_amount
        if ($status === 'rejected' && $price !== null) {
            $quote = $this->quoteModel->find($row['quote_id']);
            if ($quote) {
                $currentBalance = (float)($quote['balance_amount'] ?? 0);
                $newBalance = max(0, $currentBalance - $price);
                $this->quoteModel->update($row['quote_id'], [
                    'balance_amount' => $newBalance,
                ]);

                $paymentService = new \App\Application\Payments\PaymentService();
                $paymentService->syncTranche2Amount((string)$row['quote_id']);
            }
        }

        return Result::ok(['data' => $this->model->find($id)]);
    }

    public function delete(string $id): Result
    {
        $existing = $this->model->find($id);
        if (!$existing) return Result::notFound('Addon introuvable.');
        $this->model->delete($id);
        return Result::ok(['message' => 'Addon supprimé.']);
    }
}
