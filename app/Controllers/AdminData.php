<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class AdminData extends ResourceController
{
    protected $format = 'json';

    public function truncateTestData()
    {
        try {
            $db = \Config\Database::connect();

            $tables = [
                'attachments',
                'bon_livraison',
                'achats',
                'produits',
                'notifications',
                'payments',
                'commandes',
                'quotes',
                'demandes_client',
                'production_checklists',
                'assemblages',
                'production_workflows',
                'audit_logs',
            ];

            $truncated = [];
            foreach ($tables as $table) {
                if ($db->tableExists($table)) {
                    $db->table($table)->truncate();
                    $truncated[] = $table;
                }
            }

            return $this->respond([
                'message' => 'Données de test supprimées avec succès.',
                'truncated' => $truncated,
            ]);
        } catch (\Throwable $e) {
            return $this->fail(['error' => $e->getMessage()], 500);
        }
    }
}
