<?php

namespace App\Application\QuoteCheckpoints;

use App\Application\Shared\Result;
use App\Models\QuoteCheckpointModel;

class QuoteCheckpointService
{
    private QuoteCheckpointModel $model;

    public function __construct(?QuoteCheckpointModel $model = null)
    {
        $this->model = $model ?? new QuoteCheckpointModel();
    }

    public function listByQuote(string $quoteId): Result
    {
        $rows = $this->model->where('quote_id', $quoteId)->orderBy('sort_order', 'ASC')->findAll();
        return Result::ok(['data' => $rows]);
    }

    public function getById(string $id): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Checkpoint introuvable.');
        return Result::ok(['data' => $row]);
    }

    public function create(array $data): Result
    {
        if (empty($data['quote_id']) || empty($data['title'])) {
            return Result::fail(['error' => 'quote_id et title sont requis.'], 422);
        }
        if (!$this->model->insert($data)) {
            return Result::fail(['error' => 'Erreur lors de la création.', 'messages' => $this->model->errors()], 422);
        }
        $id = $this->model->getInsertID();
        return Result::created(['data' => $this->model->find($id)]);
    }

    public function validate(string $id, string $userName): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Checkpoint introuvable.');
        if ($row['status'] === 'done') {
            return Result::fail(['error' => 'Ce checkpoint est déjà validé.'], 422);
        }
        $this->model->update($id, [
            'status' => 'done',
            'validated_at' => date('Y-m-d H:i:s'),
            'validated_by' => $userName,
        ]);
        return Result::ok(['data' => $this->model->find($id)]);
    }

    public function delete(string $id): Result
    {
        $existing = $this->model->find($id);
        if (!$existing) return Result::notFound('Checkpoint introuvable.');
        $this->model->delete($id);
        return Result::ok(['message' => 'Checkpoint supprimé.']);
    }
}
