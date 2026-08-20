<?php

namespace App\Models;

use CodeIgniter\Model;

class PointFideliteModel extends Model
{
    protected $table = 'points_fidelite';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id', 'points', 'motif', 'reference_type', 'reference_id',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'created_at';
    protected $validationRules = [
        'user_id' => 'required',
        'points' => 'required|integer',
        'motif' => 'required|max_length[100]',
    ];
    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        return $data;
    }

    public function balance(string $userId): int
    {
        $row = $this->selectSum('points', 'total')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();
        return (int) ($row['total'] ?? 0);
    }
}