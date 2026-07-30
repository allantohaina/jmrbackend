<?php

namespace App\Application\Commandes;

use App\Application\Shared\Result;
use App\Models\CommandeModel;

class CommandeService
{
    private CommandeModel $model;

    public function __construct()
    {
        $this->model = new CommandeModel();
    }

    public function list(?string $userId = null): Result
    {
        if ($userId) {
            $commandes = $this->model->where('client_id', $userId)->orderBy('created_at', 'DESC')->findAll();
        } else {
            $commandes = $this->model->getCommandesWithClient();
        }
        return Result::ok(['data' => $commandes]);
    }

    public function getById(string $id): Result
    {
        $commande = $this->model->getCommandeWithClient($id);
        if (!$commande) {
            return Result::notFound('Commande introuvable.');
        }
        return Result::ok(['data' => $commande]);
    }

    public function create(array $data): Result
    {
        $data['date_commande'] = $data['date_commande'] ?? date('Y-m-d');
        $data['statut_production'] = $data['statut_production'] ?? 'En attente matière';
        $data['total'] = ($data['quantite'] ?? 0) * ($data['prix_unitaire'] ?? 0);

        if (!$this->model->insert($data)) {
            return Result::fail(['error' => 'Erreur lors de la création de la commande.', 'messages' => $this->model->errors()], 500);
        }

        $commande = $this->model->find($this->model->getInsertID());
        return Result::created(['data' => $commande]);
    }

    public function update(string $id, array $data): Result
    {
        $commande = $this->model->find($id);
        if (!$commande) {
            return Result::notFound('Commande introuvable.');
        }

        if (isset($data['quantite']) || isset($data['prix_unitaire'])) {
            $qte = $data['quantite'] ?? $commande['quantite'];
            $pu = $data['prix_unitaire'] ?? $commande['prix_unitaire'];
            $data['total'] = $qte * $pu;
        }

        if (!$this->model->update($id, $data)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour.', 'messages' => $this->model->errors()], 500);
        }

        return Result::ok(['data' => $this->model->find($id)]);
    }

    public function delete(string $id): Result
    {
        $commande = $this->model->find($id);
        if (!$commande) {
            return Result::notFound('Commande introuvable.');
        }

        $this->model->delete($id);
        return Result::ok(['message' => 'Commande supprimée.']);
    }
}
