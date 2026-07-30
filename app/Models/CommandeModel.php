<?php

namespace App\Models;

use CodeIgniter\Model;

class CommandeModel extends Model
{
    protected $table = 'commandes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'id', 'cotation_id', 'client_id', 'numero', 'designation',
        'quantite', 'prix_unitaire', 'total', 'statut_production',
        'pieces_produites', 'date_commande', 'date_livraison_prevue',
        'date_livraison_reelle', 'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'numero' => 'required|max_length[50]',
        'client_id' => 'required',
        'quantite' => 'permit_empty|numeric',
        'prix_unitaire' => 'permit_empty|numeric',
    ];

    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) {
            $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        }
        return $data;
    }

    public function getCommandesWithClient(): array
    {
        return $this->select('commandes.*, u.email as client_email, u.first_name as client_first_name, u.last_name as client_last_name')
            ->join('users u', 'u.id = commandes.client_id', 'left')
            ->orderBy('commandes.created_at', 'DESC')
            ->findAll();
    }

    public function getCommandeWithClient($id): ?array
    {
        return $this->select('commandes.*, u.email as client_email, u.first_name as client_first_name, u.last_name as client_last_name')
            ->join('users u', 'u.id = commandes.client_id', 'left')
            ->find($id);
    }
}
