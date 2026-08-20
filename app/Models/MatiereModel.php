<?php

namespace App\Models;

use CodeIgniter\Model;

class MatiereModel extends Model
{
    protected $table = 'matieres';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'nom', 'unite', 'stock_actuel', 'stock_seuil', 'prix_unite', 'fournisseur', 'description',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
        'nom' => 'required|min_length[2]|max_length[255]',
    ];
    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        return $data;
    }
}