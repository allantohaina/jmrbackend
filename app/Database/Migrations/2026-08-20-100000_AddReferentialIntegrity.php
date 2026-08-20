<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReferentialIntegrity extends Migration
{
    /**
     * Contraintes réelles manquantes : les migrations antérieures utilisaient
     * forge->addForeignKey() sans createTable(), ce qui ne génère aucune
     * contrainte en base. Cette migration ajoute les vraies FK de façon
     * portable (PostgreSQL / MySQL / Oracle) avec nettoyage des orphelins.
     *
     * Remarque : order_project_history.project_id -> production_workflows(id)
     * est volontairement exclu (types incompatibles sur PostgreSQL historique).
     */
    private const FK_LIST = [
        ['table' => 'quotes', 'column' => 'client_id', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'SET NULL', 'name' => 'fk_quotes_client'],
        ['table' => 'quotes', 'column' => 'produit_id', 'ref' => 'produits', 'refcol' => 'id', 'onDelete' => 'SET NULL', 'name' => 'fk_quotes_produit'],
        ['table' => 'refresh_tokens', 'column' => 'user_id', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'CASCADE', 'name' => 'fk_refresh_user'],
        ['table' => 'user_consents', 'column' => 'user_id', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'CASCADE', 'name' => 'fk_consents_user'],
        ['table' => 'data_requests', 'column' => 'user_id', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'CASCADE', 'name' => 'fk_datareq_user'],
        ['table' => 'audit_logs', 'column' => 'actor_user_id', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'SET NULL', 'name' => 'fk_audit_actor'],
        ['table' => 'token_history', 'column' => 'user_id', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'CASCADE', 'name' => 'fk_tokhist_user'],
        ['table' => 'token_history', 'column' => 'refresh_token_id', 'ref' => 'refresh_tokens', 'refcol' => 'id', 'onDelete' => 'SET NULL', 'name' => 'fk_tokhist_token'],
        ['table' => 'order_project_history', 'column' => 'actor_user_id', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'SET NULL', 'name' => 'fk_oph_actor'],
        ['table' => 'payments', 'column' => 'submitted_by', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'SET NULL', 'name' => 'fk_pay_submitted'],
        ['table' => 'payments', 'column' => 'reviewed_by', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'SET NULL', 'name' => 'fk_pay_reviewed'],
        ['table' => 'notifications', 'column' => 'actor_user_id', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'SET NULL', 'name' => 'fk_notif_actor'],
        ['table' => 'attachments', 'column' => 'uploaded_by', 'ref' => 'users', 'refcol' => 'id', 'onDelete' => 'SET NULL', 'name' => 'fk_attach_user'],
        ['table' => 'quote_checkpoints', 'column' => 'commande_id', 'ref' => 'commandes', 'refcol' => 'id', 'onDelete' => 'CASCADE', 'name' => 'fk_qcheck_commande'],
        ['table' => 'quote_addons', 'column' => 'commande_id', 'ref' => 'commandes', 'refcol' => 'id', 'onDelete' => 'CASCADE', 'name' => 'fk_qaddon_commande'],
    ];

    public function up()
    {
        foreach (self::FK_LIST as $fk) {
            $this->addForeignKeyIfMissing($fk);
        }
    }

    public function down()
    {
        foreach (array_reverse(self::FK_LIST) as $fk) {
            $this->dropForeignKeyIfExists($fk);
        }
    }

    private function driver(): string
    {
        return strtolower($this->db->DBDriver);
    }

    private function constraintExists(string $name): bool
    {
        $driver = $this->driver();
        if ($driver === 'mysqli') {
            $sql = 'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ?';
        } elseif ($driver === 'postgre') {
            $sql = 'SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = current_schema() AND constraint_name = ?';
        } else {
            $sql = 'SELECT COUNT(*) FROM user_constraints WHERE constraint_name = ?';
            $name = strtoupper($name);
        }

        $row = $this->db->query($sql, [$name])->getRowArray();
        if (!is_array($row)) {
            return false;
        }

        return (int) array_values($row)[0] > 0;
    }

    private function isColumnNullable(string $table, string $column): bool
    {
        foreach ($this->db->getFieldData($table) as $field) {
            if (($field->name ?? '') === $column) {
                return (bool) ($field->nullable ?? false);
            }
        }

        return true;
    }

    private function cleanupOrphans(string $table, string $column, string $refTable, string $refColumn): void
    {
        if (!$this->db->tableExists($table) || !$this->db->tableExists($refTable)) {
            return;
        }
        if (!$this->db->fieldExists($column, $table) || !$this->db->fieldExists($refColumn, $refTable)) {
            return;
        }

        $tbl = $this->db->protectIdentifiers($table);
        $col = $this->db->protectIdentifiers($column);
        $refTbl = $this->db->protectIdentifiers($refTable);
        $refCol = $this->db->protectIdentifiers($refColumn);

        if ($this->isColumnNullable($table, $column)) {
            $sql = "UPDATE {$tbl} SET {$col} = NULL WHERE {$col} IS NOT NULL AND {$col} NOT IN (SELECT {$refCol} FROM {$refTbl})";
        } else {
            $sql = "DELETE FROM {$tbl} WHERE {$col} NOT IN (SELECT {$refCol} FROM {$refTbl})";
        }
        $this->db->query($sql);
    }

    private function addForeignKeyIfMissing(array $fk): void
    {
        $this->db->resetDataCache();
        if ($this->constraintExists($fk['name'])) {
            return;
        }
        if (!$this->db->tableExists($fk['table']) || !$this->db->tableExists($fk['ref'])) {
            return;
        }
        if (!$this->db->fieldExists($fk['column'], $fk['table']) || !$this->db->fieldExists($fk['refcol'], $fk['ref'])) {
            return;
        }

        $this->cleanupOrphans($fk['table'], $fk['column'], $fk['ref'], $fk['refcol']);

        $tbl = $this->db->protectIdentifiers($fk['table']);
        $col = $this->db->protectIdentifiers($fk['column']);
        $refTbl = $this->db->protectIdentifiers($fk['ref']);
        $refCol = $this->db->protectIdentifiers($fk['refcol']);

        $sql = "ALTER TABLE {$tbl} ADD CONSTRAINT {$fk['name']} FOREIGN KEY ({$col}) REFERENCES {$refTbl} ({$refCol})";
        if ($fk['onDelete'] !== '') {
            $sql .= ' ON DELETE ' . $fk['onDelete'];
        }
        $this->db->query($sql);
    }

    private function dropForeignKeyIfExists(array $fk): void
    {
        if (!$this->constraintExists($fk['name']) || !$this->db->tableExists($fk['table'])) {
            return;
        }
        $tbl = $this->db->protectIdentifiers($fk['table']);
        if ($this->driver() === 'mysqli') {
            $sql = "ALTER TABLE {$tbl} DROP FOREIGN KEY {$fk['name']}";
        } else {
            $sql = "ALTER TABLE {$tbl} DROP CONSTRAINT {$fk['name']}";
        }
        $this->db->query($sql);
    }
}