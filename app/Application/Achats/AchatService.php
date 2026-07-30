<?php

namespace App\Application\Achats;

use App\Application\Shared\Result;
use App\Models\AchatModel;

class AchatService
{
    private AchatModel $model;

    public function __construct()
    {
        $this->model = new AchatModel();
    }

    public function list(): Result
    {
        $achats = $this->model->orderBy('created_at', 'DESC')->findAll();
        return Result::ok(['data' => $achats]);
    }

    public function getById(string $id): Result
    {
        $achat = $this->model->find($id);
        if (!$achat) {
            return Result::notFound('Achat introuvable.');
        }
        return Result::ok(['data' => $achat]);
    }

    public function create(array $data): Result
    {
        $data['date_achat'] = $data['date_achat'] ?? date('Y-m-d');
        $data['statut'] = $data['statut'] ?? 'En attente';

        if (!$this->model->insert($data)) {
            return Result::fail(['error' => 'Erreur lors de la création de l\'achat.', 'messages' => $this->model->errors()], 500);
        }

        $achat = $this->model->find($this->model->getInsertID());
        return Result::created(['data' => $achat]);
    }

    public function update(string $id, array $data): Result
    {
        $achat = $this->model->find($id);
        if (!$achat) {
            return Result::notFound('Achat introuvable.');
        }

        if (!$this->model->update($id, $data)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour.', 'messages' => $this->model->errors()], 500);
        }

        return Result::ok(['data' => $this->model->find($id)]);
    }

    public function delete(string $id): Result
    {
        $achat = $this->model->find($id);
        if (!$achat) {
            return Result::notFound('Achat introuvable.');
        }

        $this->model->delete($id);
        return Result::ok(['message' => 'Achat supprimé.']);
    }
}
