<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    private array $indexes = [
        'users_email'                 => ['users', 'users (email)'],
        'users_role'                  => ['users', 'users (role)'],
        'idx_payments_quote'          => ['payments', 'payments (quote_id)'],
        'idx_payments_commande'       => ['payments', 'payments (commande_id)'],
        'idx_payments_status_created' => ['payments', 'payments (status, created_at)'],
        'idx_commandes_created'       => ['commandes', 'commandes (created_at)'],
        'idx_commandes_statut_prevue' => ['commandes', 'commandes (statut_production, date_livraison_prevue)'],
        'idx_quotes_created'          => ['quotes', 'quotes (created_at)'],
        'idx_avis_produit_statut'     => ['avis_produits', 'avis_produits (produit_id, statut)'],
        'idx_avis_statut_created'     => ['avis_produits', 'avis_produits (statut, created_at)'],
        'idx_matieres_nom'            => ['matieres', 'matieres (nom)'],
        'idx_achats_created'          => ['achats', 'achats (created_at)'],
        'idx_bon_livraison_created'   => ['bons_livraison', 'bons_livraison (created_at)'],
        'idx_points_user'             => ['points_fidelite', 'points_fidelite (user_id)'],
    ];

    public function up()
    {
        foreach ($this->indexes as $name => [$table, $definition]) {
            if (!$this->indexExists($table, $name)) {
                $this->db->query("CREATE INDEX {$name} ON {$definition}");
            }
        }
    }

    public function down()
    {
        $driver = strtolower($this->db->DBDriver);
        foreach (array_keys($this->indexes) as $name) {
            if ($this->indexExists($this->indexes[$name][0], $name)) {
                if (in_array($driver, ['mysqli', 'mysql'], true)) {
                    $this->db->query("DROP INDEX {$name} ON {$this->indexes[$name][0]}");
                } else {
                    $this->db->query("DROP INDEX IF EXISTS {$name}");
                }
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = strtolower($this->db->DBDriver);
        if (in_array($driver, ['mysqli', 'mysql'], true)) {
            $row = $this->db->query(
                'SELECT COUNT(*) AS c FROM information_schema.statistics '
                . 'WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                [$table, $indexName]
            )->getRowArray();
            return (int) ($row['c'] ?? 0) > 0;
        }
        $row = $this->db->query(
            'SELECT COUNT(*) AS c FROM pg_indexes '
            . 'WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
            [$table, $indexName]
        )->getRowArray();
        return (int) ($row['c'] ?? 0) > 0;
    }
}