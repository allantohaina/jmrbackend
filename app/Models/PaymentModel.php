<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'quote_id', 'commande_id', 'phase', 'amount', 'status', 'proof_path',
        'submitted_by', 'reviewed_by', 'review_note', 'reviewed_at',
        'payment_type', 'transaction_ref',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'quote_id' => 'required',
        'phase' => 'required|in_list[deposit,balance]',
        'amount' => 'required|decimal',
        'status' => 'required|in_list[submitted,verified,rejected]',
    ];
    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        return $data;
    }
}
