<?php

namespace App\Models;

use CodeIgniter\Model;

class LienPaiementModel extends Model
{
    protected $table = 'liens_paiement';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'commande_id', 'token', 'montant', 'statut', 'expire_at', 'paid_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'commande_id' => 'required',
        'token' => 'required|min_length[16]',
        'montant' => 'required|decimal|greater_than[0]',
        'statut' => 'in_list[pending,paid,expired,cancelled]',
    ];
    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        return $data;
    }
}