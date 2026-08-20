<?php

namespace App\Application\BonLivraison;

use App\Application\Shared\Result;
use App\Models\BonLivraisonModel;

class BonLivraisonService
{
    private BonLivraisonModel $model;

    public function __construct()
    {
        $this->model = new BonLivraisonModel();
    }

    public function list(): Result
    {
        $bons = $this->model->getWithCommande();
        return Result::ok(['data' => $bons]);
    }

    public function getById(string $id): Result
    {
        $bon = $this->model->find($id);
        if (!$bon) {
            return Result::notFound('Bon de livraison introuvable.');
        }

        if (is_string($bon['articles'])) {
            $bon['articles'] = json_decode($bon['articles'], true);
        }

        return Result::ok(['data' => $bon]);
    }

    public function create(array $data): Result
    {
        $data['date_livraison'] = $data['date_livraison'] ?? date('Y-m-d');
        $data['statut'] = $data['statut'] ?? 'Préparé';

        if (isset($data['articles']) && is_array($data['articles'])) {
            $data['articles'] = json_encode($data['articles']);
        }

        if (!$this->model->insert($data)) {
            return Result::fail(['error' => 'Erreur lors de la création du bon de livraison.', 'messages' => $this->model->errors()], 422);
        }

        $bon = $this->model->find($this->model->getInsertID());
        if (is_string($bon['articles'])) {
            $bon['articles'] = json_decode($bon['articles'], true);
        }

        return Result::created(['data' => $bon]);
    }

    public function update(string $id, array $data): Result
    {
        $bon = $this->model->find($id);
        if (!$bon) {
            return Result::notFound('Bon de livraison introuvable.');
        }

        if (isset($data['articles']) && is_array($data['articles'])) {
            $data['articles'] = json_encode($data['articles']);
        }

        if (!$this->model->update($id, $data)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour.', 'messages' => $this->model->errors()], 422);
        }

        $updated = $this->model->find($id);
        if (is_string($updated['articles'])) {
            $updated['articles'] = json_decode($updated['articles'], true);
        }

        return Result::ok(['data' => $updated]);
    }

    public function delete(string $id): Result
    {
        $bon = $this->model->find($id);
        if (!$bon) {
            return Result::notFound('Bon de livraison introuvable.');
        }

        $this->model->delete($id);
        return Result::ok(['message' => 'Bon de livraison supprimé.']);
    }
}
