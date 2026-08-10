<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class AdminData extends ResourceController
{
    protected $format = 'json';

    protected $protectedTables = [
        'users',
        'tokens',
        'site_content',
        'ban_blacklist',
        'ip_blocklist',
    ];

    public function truncateTestData()
    {
        try {
            $json = $this->request->getJSON(true);
            $password = $json['password'] ?? '';

            if (empty($password)) {
                return $this->fail(['error' => 'Mot de passe requis pour confirmer cette action.'], 400);
            }

            // Verify admin password
            $userId = $this->request->user['id'] ?? null;
            if (!$userId) {
                return $this->fail(['error' => 'Non autorisé.'], 401);
            }

            $userModel = new \App\Models\UserModel();
            $user = $userModel->find($userId);

            if (!$user) {
                return $this->fail(['error' => 'Utilisateur introuvable.'], 401);
            }

            if (!password_verify($password, $user['password_hash'])) {
                return $this->fail(['error' => 'Mot de passe incorrect.'], 403);
            }

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

            // Safety: never truncate protected tables
            $tables = array_diff($tables, $this->protectedTables);

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
