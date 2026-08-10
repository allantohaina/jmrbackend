<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteDraftModel extends Model
{
    protected $table = 'quote_drafts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = false;
    protected $allowedFields = [
        'client_id',
        'payload',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (!isset($data['data']['id'])) {
            $data['data']['id'] = $this->uuidV4();
        }
        return $data;
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }

    public function findForClient(string $id, string $clientId): ?array
    {
        $draft = $this->where('id', $id)->where('client_id', $clientId)->first();
        if (!$draft) return null;
        $draft['payload'] = is_string($draft['payload'] ?? null)
            ? json_decode($draft['payload'], true)
            : ($draft['payload'] ?? []);
        return $draft;
    }

    public function listForClient(string $clientId): array
    {
        $drafts = $this->where('client_id', $clientId)->orderBy('updated_at', 'DESC')->findAll();
        foreach ($drafts as &$draft) {
            $draft['payload'] = is_string($draft['payload'] ?? null)
                ? json_decode($draft['payload'], true)
                : ($draft['payload'] ?? []);
        }
        return $drafts;
    }
}