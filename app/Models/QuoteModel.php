<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteModel extends Model
{
    protected $table = 'quotes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'email',
        'phone',
        'message',
        'category',
        'tissu',
        'coupe',
        'gabarit',
        'style',
        'grammage',
        'tailles',
        'quantite',
        'finitions',
        'delai_souhaite',
        'request_type',
        'modify_code',
        'status',
        'amount',
        'deposit_amount',
        'balance_amount',
        'deposit_paid',
        'balance_paid',
        'files',
        'notifications',
        'admin_signature_name',
        'admin_signature_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[255]',
        'email' => 'required|valid_email|max_length[255]',
        'message' => 'required|min_length[20]',
    ];

    protected $validationMessages = [];

    protected $beforeInsert = ['generateUUID'];

    /**
     * Generate UUID for new quotes
     */
    protected function generateUUID(array $data): array
    {
        if (!isset($data['data']['id'])) {
            $data['data']['id'] = $this->uuidV4();
        }
        return $data;
    }

    /**
     * Generate a UUID v4 string.
     */
    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }

    /**
     * Get quote by ID
     */
    public function getQuoteById(string $id): ?array
    {
        return $this->find($id);
    }

    /**
     * Get all quotes
     */
    public function getAllQuotes(int $limit = 0, int $offset = 0): array
    {
        $this->select('id, name, email, phone, message, category, status, amount, deposit_amount, balance_amount, deposit_paid, balance_paid, request_type, modify_code, created_at, updated_at');
        if ($limit > 0) {
            return $this->limit($limit, $offset)->findAll();
        }
        return $this->findAll();
    }

    /**
     * Get total count of quotes
     */
    public function countAll(): int
    {
        $this->select('id');
        return $this->countAllResults();
    }
}
