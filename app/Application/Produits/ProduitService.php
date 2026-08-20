<?php

namespace App\Application\Produits;

use App\Application\Shared\Result;
use App\Models\ProduitModel;

class ProduitService
{
    private ProduitModel $model;

    public function __construct(?ProduitModel $model = null)
    {
        $this->model = $model ?? new ProduitModel();
    }

    public function list(): Result
    {
        return Result::ok(['data' => $this->model->findAllWithDecoded()]);
    }

    public function categories(): Result
    {
        return Result::ok(['data' => $this->model->listCategories()]);
    }

    public function getById(string $id): Result
    {
        $row = $this->model->findWithDecoded($id);
        if (!$row) return Result::notFound('Produit introuvable.');
        return Result::ok(['data' => $row]);
    }

    public function create(array $data): Result
    {
        if (empty($data['nom'])) {
            return Result::fail(['error' => 'Le nom du produit est requis.'], 422);
        }
        if (!$this->model->insert($data)) {
            return Result::fail(['error' => 'Erreur lors de la création du produit.', 'messages' => $this->model->errors()], 422);
        }
        $id = $this->model->getInsertID();
        return Result::created(['data' => $this->model->findWithDecoded($id)]);
    }

    public function update(string $id, array $data): Result
    {
        $existing = $this->model->find($id);
        if (!$existing) return Result::notFound('Produit introuvable.');
        if (isset($data['id'])) unset($data['id']);
        if (!$this->model->update($id, $data)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour du produit.', 'messages' => $this->model->errors()], 422);
        }
        return Result::ok(['data' => $this->model->findWithDecoded($id)]);
    }

    public function delete(string $id): Result
    {
        $existing = $this->model->find($id);
        if (!$existing) return Result::notFound('Produit introuvable.');
        $this->model->delete($id);
        return Result::ok(['message' => 'Produit supprimé.']);
    }
}
