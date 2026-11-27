<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductionTicketModel extends Model
{
    protected $table = 'production_tickets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';

    protected $allowedFields = [
        'id', 'commande_id', 'quote_id', 'status',
        'changes_locked_at', 'created_by', 'closed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data): array
    {
        if (empty($data['data']['id'])) {
            $data['data']['id'] = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }
        return $data;
    }

    /** Ferme tous les tickets ouverts rattachés au devis. */
    public function closeForQuote(string $quoteId): int
    {
        $rows = $this->where('quote_id', $quoteId)->where('closed_at IS NULL')->findAll();
        foreach ($rows as $row) {
            $this->update($row['id'], [
                'status' => 'closed',
                'closed_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return count($rows);
    }
}
