<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteAddonModel extends Model
{
    protected $table = 'quote_addons';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = false;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'id', 'quote_id', 'commande_id', 'title', 'description',
        'price', 'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'quote_id' => 'required',
        'title' => 'required|max_length[255]',
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
