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
