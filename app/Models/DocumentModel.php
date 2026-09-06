<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'id', 'client_id', 'devis_id', 'type',
        'nom_original', 'chemin_stocke', 'mime_type',
        'taille_bytes', 'uploaded_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'client_id' => 'required|max_length[36]',
        'type' => 'required|in_list[preuve_paiement,devis,recu]',
        'nom_original' => 'required|max_length[255]',
        'chemin_stocke' => 'required|max_length[500]',
        'mime_type' => 'required|max_length[255]',
        'taille_bytes' => 'required|numeric',
        'uploaded_by' => 'required|max_length[36]',
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
