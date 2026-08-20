<?php

namespace App\Models;

use CodeIgniter\Model;

class AvisProduitModel extends Model
{
    protected $table = 'avis_produits';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'produit_id', 'user_id', 'note', 'commentaire', 'statut',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'produit_id' => 'required',
        'note' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
        'statut' => 'in_list[pending,approved,rejected]',
    ];
    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        return $data;
    }
}