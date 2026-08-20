<?php

namespace App\Models;

use CodeIgniter\Model;

class MouvementStockModel extends Model
{
    protected $table = 'mouvements_stock';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'matiere_id', 'type', 'quantite', 'motif', 'reference_type', 'reference_id',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'created_at';
    protected $validationRules = [
        'matiere_id' => 'required',
        'type' => 'required|in_list[entree,sortie,ajustement]',
        'quantite' => 'required|numeric|greater_than[0]',
    ];
    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        return $data;
    }
}