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
        $cached = cache('produits_list');
        if ($cached !== null) {
            return Result::ok(['data' => $cached]);
        }
        $rows = $this->model->findAllWithDecoded();
        cache('produits_list', $rows, 300);
        return Result::ok(['data' => $rows]);
    }

    public function categories(): Result
    {
        $cached = cache('produits_categories');
        if ($cached !== null) {
            return Result::ok(['data' => $cached]);
        }
        $rows = $this->model->listCategories();
        cache('produits_categories', $rows, 300);
        return Result::ok(['data' => $rows]);
    }

    public function getById(string $id): Result
    {
        $cacheKey = 'produits_' . $id;
        $cached = cache($cacheKey);
        if ($cached !== null) {
            return Result::ok(['data' => $cached]);
        }
        $row = $this->model->findWithDecoded($id);
        if (!$row) return Result::notFound('Produit introuvable.');
        cache($cacheKey, $row, 300);
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
        $this->invalidateCache();
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
        $this->invalidateCache($id);
        return Result::ok(['data' => $this->model->findWithDecoded($id)]);
    }

    public function delete(string $id): Result
    {
        $existing = $this->model->find($id);
        if (!$existing) return Result::notFound('Produit introuvable.');
        $this->model->delete($id);
        $this->invalidateCache($id);
        return Result::ok(['message' => 'Produit supprimé.']);
    }

    private function invalidateCache(?string $id = null): void
    {
        cache()->delete('produits_list');
        cache()->delete('produits_categories');
        if ($id !== null) {
            cache()->delete('produits_' . $id);
        }
    }
}
