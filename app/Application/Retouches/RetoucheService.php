<?php

namespace App\Application\Retouches;

use App\Application\QuoteAddons\QuoteAddonService;
use App\Application\Shared\Result;
use App\Models\RetoucheModel;

/** Retouches/corrections en production ; l'inclusion crée un élément payant. */
class RetoucheService
{
    private RetoucheModel $model;

    public function __construct()
    {
        $this->model = new RetoucheModel();
    }

    public function listByQuote(string $quoteId): Result
    {
        return Result::ok([
            'data' => $this->model->where('quote_id', $quoteId)->orderBy('created_at', 'DESC')->findAll(),
        ]);
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
        return Result::created(['data' => $this->model->find($this->model->getInsertID())]);
    }

    /** Valide la retouche et ajoute son prix au devis via le mécanisme des éléments. */
    public function includeRetouche(string $id, ?float $price = null): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Retouche introuvable.');
        if (($row['status'] ?? '') === 'included') return Result::ok(['data' => $row]);

        $effectivePrice = $price ?? (float)($row['price'] ?? 0);
        if ($effectivePrice <= 0) {
            return Result::fail(['error' => 'Un prix doit être renseigné avant d’inclure la retouche.'], 422);
        }

        $addonService = new QuoteAddonService();
        $addon = $addonService->create([
            'quote_id' => $row['quote_id'],
            'title' => $row['title'],
            'description' => $row['description'] ?? null,
            'price' => $effectivePrice,
            'commande_id' => $row['commande_id'] ?? null,
        ]);
        if (!$addon->isSuccess()) return $addon;

        $addonId = $addon->getPayload()['data']['id'] ?? null;
        if ($addonId) {
            $included = $addonService->updateStatus((string)$addonId, 'included', $effectivePrice);
            if (!$included->isSuccess()) return $included;
        }

        $this->model->update($id, ['status' => 'included', 'price' => $effectivePrice]);
        return Result::ok(['data' => $this->model->find($id)]);
    }

    public function reject(string $id): Result
    {
        if (!$this->model->find($id)) return Result::notFound('Retouche introuvable.');
        $this->model->update($id, ['status' => 'rejected']);
        return Result::ok(['data' => $this->model->find($id)]);
    }
}
