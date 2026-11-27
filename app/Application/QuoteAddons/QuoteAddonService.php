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
            return Result::fail(['error' => 'Erreur lors de la création.', 'messages' => $this->model->errors()], 422);
        }
        $id = $this->model->getInsertID();
        return Result::created(['data' => $this->model->find($id)]);
    }

    public function updateStatus(string $id, string $status, ?float $price = null): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Addon introuvable.');

        $oldStatus = (string)($row['status'] ?? 'pending');
        $effectivePrice = $price !== null ? $price : (float)($row['price'] ?? 0);

        $updateData = ['status' => $status];
        if ($price !== null) $updateData['price'] = $price;
        if (!$this->model->update($id, $updateData)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour.'], 422);
        }

        // Un seul delta : ajout quand on inclut, retrait quand on annule une inclusion.
        $delta = 0.0;
        if ($status === 'included' && $oldStatus !== 'included') $delta = $effectivePrice;
        if ($status === 'rejected' && $oldStatus === 'included') $delta = -$effectivePrice;

        if ($delta !== 0.0) {
            $quote = $this->quoteModel->find($row['quote_id']);
            if ($quote) {
                $this->quoteModel->update($row['quote_id'], [
                    'amount' => max(0, (float)($quote['amount'] ?? 0) + $delta),
                    'prix_total_calcule' => max(0, (float)($quote['prix_total_calcule'] ?? 0) + $delta),
                    'balance_amount' => max(0, (float)($quote['balance_amount'] ?? 0) + $delta),
                ]);
                (new \App\Application\Payments\PaymentService())->syncTranche2Amount((string)$row['quote_id']);
                $this->notifyAddon($row, $status, $effectivePrice);
            }
        }

        return Result::ok(['data' => $this->model->find($id)]);
    }

    private function notifyAddon(array $addon, string $status, float $price): void
    {
        try {
            $quote = $this->quoteModel->find($addon['quote_id']);
            if (!$quote) return;
            $userModel = new \App\Models\UserModel();
            $client = null;
            if (!empty($quote['client_id'])) $client = $userModel->find($quote['client_id']);
            if (!$client && !empty($quote['email'])) {
                $row = \Config\Database::connect()
                    ->query('SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1', [strtolower((string)$quote['email'])])
                    ->getRowArray();
                if ($row) $client = $row;
            }
            if (!$client) return;
            $included = $status === 'included';
            (new \App\Application\Notifications\NotificationService())->create(
                (string)$client['id'],
                'quote.addon',
                $included ? 'Élément ajouté au devis' : 'Élément retiré du devis',
                ($included ? 'Un élément supplémentaire a été ajouté : ' : 'Un élément a été retiré : ')
                    . (string)($addon['title'] ?? 'Prestation') . ' — ' . number_format($price, 2, ',', ' ') . ' Ar',
                'quote', (string)$addon['quote_id'],
                '/mon-profil/devis/detail?id=' . $addon['quote_id'],
                null, $included ? 'success' : 'warning'
            );
        } catch (\Throwable $e) {
            log_message('error', 'QuoteAddonService notifyAddon: ' . $e->getMessage());
        }
    }

    public function delete(string $id): Result
    {
        $existing = $this->model->find($id);
        if (!$existing) return Result::notFound('Addon introuvable.');
        $this->model->delete($id);
        return Result::ok(['message' => 'Addon supprimé.']);
    }
}
