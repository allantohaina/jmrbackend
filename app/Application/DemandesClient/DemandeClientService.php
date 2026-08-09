<?php

namespace App\Application\DemandesClient;

use App\Application\Shared\Result;
use App\Models\DemandeClientModel;

class DemandeClientService
{
    private DemandeClientModel $model;

    public function __construct(?DemandeClientModel $model = null)
    {
        $this->model = $model ?? new DemandeClientModel();
    }

    public function list(): Result
    {
        return Result::ok(['data' => $this->model->getAllWithDetails(), 'counts' => $this->model->countByStatut()]);
    }

    public function pendingCount(): Result
    {
        $counts = $this->model->countByStatut();
        $pending = ($counts['Nouvelle'] ?? 0) + ($counts['En cours d\'étude'] ?? 0);
        return Result::ok(['data' => [
            'pending_total' => $pending,
            'nouvelle' => $counts['Nouvelle'] ?? 0,
            'en_etude' => $counts['En cours d\'étude'] ?? 0,
            'convertie' => $counts['Convertie en cotation'] ?? 0,
            'refusee' => $counts['Refusée'] ?? 0,
        ]]);
    }

    public function getById(string $id): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Demande client introuvable.');
        return Result::ok(['data' => $row]);
    }

    public function create(array $data): Result
    {
        if (empty($data['nom_client'])) {
            return Result::fail(['error' => 'Le nom du client est requis.'], 422);
        }
        $hasContact = !empty($data['email']) || !empty($data['telephone']);
        if (!$hasContact) {
            return Result::fail(['error' => 'Au moins un contact (email ou téléphone) est requis.'], 422);
        }
        if (empty($data['description'])) {
            return Result::fail(['error' => 'La description du besoin est requise.'], 422);
        }
        if (!$this->model->insert($data)) {
            return Result::fail(['error' => 'Erreur lors de la création.', 'messages' => $this->model->errors()], 500);
        }
        $id = $this->model->getInsertID();
        return Result::created(['data' => $this->model->find($id)]);
    }

    public function update(string $id, array $data): Result
    {
        $existing = $this->model->find($id);
        if (!$existing) return Result::notFound('Demande introuvable.');
        if (isset($data['id'])) unset($data['id']);
        if (isset($data['statut']) && $data['statut'] === 'Refusée') {
        }
        if (!$this->model->update($id, $data)) {
            return Result::fail(['error' => 'Erreur mise à jour.', 'messages' => $this->model->errors()], 500);
        }
        return Result::ok(['data' => $this->model->find($id)]);
    }

    public function refuse(string $id): Result
    {
        $existing = $this->model->find($id);
        if (!$existing) return Result::notFound('Demande introuvable.');
        $this->model->update($id, ['statut' => 'Refusée']);
        return Result::ok(['data' => $this->model->find($id), 'message' => 'Demande marquée refusée (historique conservé).']);
    }

    public function linkToCotation(string $id, string $cotationId): Result
    {
        $existing = $this->model->find($id);
        if (!$existing) return Result::notFound('Demande introuvable.');
        $this->model->update($id, [
            'statut' => 'Convertie en cotation',
            'cotation_id' => $cotationId,
        ]);
        return Result::ok(['data' => $this->model->find($id)]);
    }
}
