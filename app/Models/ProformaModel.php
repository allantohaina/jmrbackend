<?php

namespace App\Models;

use CodeIgniter\Model;

class ProformaModel extends Model
{
    protected $table = 'proformas';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'id', 'number', 'seq', 'year', 'status',
        'quote_id', 'client_id',
        'client_name', 'client_email', 'client_phone', 'client_address', 'client_tax_id',
        'currency', 'tax_rate', 'discount_pct', 'deposit_pct',
        'lines', 'subtotal', 'discount_amount', 'tax_amount', 'total',
        'deposit_amount', 'balance_amount',
        'issue_date', 'valid_until', 'delivery_address', 'order_reference',
        'payment_terms', 'notes',
        'admin_signature_name', 'admin_signature_at', 'created_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) {
            $bytes = random_bytes(16);
            $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
            $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
            $hex = bin2hex($bytes);
            $data['data']['id'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
        }
        return $data;
    }

    public function nextSequence(int $year): int
    {
        $row = $this->selectMax('seq', 'max_seq')->where('year', $year)->first();
        $max = (int) ($row['max_seq'] ?? 0);
        return $max + 1;
    }

    public function decodeLines(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
