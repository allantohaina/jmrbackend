<?php

namespace App\Models;

use CodeIgniter\Model;

class AttachmentModel extends Model
{
    protected $table = 'attachments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'id', 'entity_type', 'entity_id',
        'original_name', 'stored_name', 'file_type', 'mime_type',
        'file_size', 'storage_path', 'url', 'uploaded_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'entity_type' => 'required|max_length[50]',
        'entity_id' => 'required|max_length[64]',
        'original_name' => 'required|max_length[255]',
        'stored_name' => 'required|max_length[255]',
        'file_type' => 'required|max_length[50]',
    ];

    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) {
            $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        }
        return $data;
    }

    public function findByEntity(string $entityType, string $entityId): array
    {
        return $this->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    public function countByEntity(string $entityType, string $entityId): int
    {
        return $this->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->countAllResults();
    }
}
