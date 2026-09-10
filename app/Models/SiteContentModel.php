<?php

namespace App\Models;

use CodeIgniter\Model;

class SiteContentModel extends Model
{
    protected $table = 'site_contents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = false;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'id', 'content_key', 'locale', 'type', 'value',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'content_key' => 'required|max_length[191]',
        'locale'      => 'required|in_list[fr,en]',
        'type'        => 'required|in_list[text,image]',
        'value'       => 'required',
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
