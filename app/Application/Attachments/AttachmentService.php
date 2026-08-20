<?php

namespace App\Application\Attachments;

use App\Application\Shared\Result;
use App\Models\AttachmentModel;

class AttachmentService
{
    private AttachmentModel $model;

    public const VALID_ENTITY_TYPES = ['demande', 'cotation', 'commande', 'bon_livraison', 'produit'];

    public function __construct(?AttachmentModel $model = null)
    {
        $this->model = $model ?? new AttachmentModel();
    }

    public function listByEntity(string $entityType, string $entityId): Result
    {
        if (!in_array($entityType, self::VALID_ENTITY_TYPES, true)) {
            return Result::fail(['error' => 'Type d\'entité invalide.'], 422);
        }
        return Result::ok(['data' => $this->model->findByEntity($entityType, $entityId)]);
    }

    public function getById(string $id): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Pièce jointe introuvable.');
        return Result::ok(['data' => $row]);
    }

    public function create(array $data): Result
    {
        if (empty($data['entity_type']) || !in_array($data['entity_type'], self::VALID_ENTITY_TYPES, true)) {
            return Result::fail(['error' => 'Type d\'entité invalide.'], 422);
        }
        if (empty($data['entity_id'])) {
            return Result::fail(['error' => 'ID d\'entité requis.'], 422);
        }
        if (empty($data['original_name']) || empty($data['stored_name']) || empty($data['file_type'])) {
            return Result::fail(['error' => 'Champs fichier manquants.'], 422);
        }
        if (!$this->model->insert($data)) {
            return Result::fail(['error' => 'Erreur création pièce jointe.', 'messages' => $this->model->errors()], 422);
        }
        $id = $this->model->getInsertID();
        return Result::created(['data' => $this->model->find($id)]);
    }

    public function delete(string $id, ?string $uploadedBy = null): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Pièce jointe introuvable.');
        if ($uploadedBy !== null && ($row['uploaded_by'] ?? null) !== $uploadedBy) {
            return Result::forbidden('Vous n\'êtes pas autorisé à supprimer ce fichier.');
        }
        $this->model->delete($id);
        return Result::ok(['message' => 'Pièce jointe supprimée.']);
    }
}
