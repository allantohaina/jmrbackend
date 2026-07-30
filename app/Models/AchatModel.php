<?php

namespace App\Models;

use CodeIgniter\Model;

class AchatModel extends Model
{
    protected $table = 'achats';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = false;
    protected $returnType = 'array';

    protected $allowedFields = [
        'id', 'fournisseur', 'categorie', 'montant',
        'date_achat', 'statut', 'description',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'fournisseur' => 'required|max_length[255]',
        'categorie' => 'required|max_length[50]',
        'montant' => 'permit_empty|numeric',
    ];

    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) {
            $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        }
        return $data;
    }
}
