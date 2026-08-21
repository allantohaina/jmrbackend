<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakePaymentQuoteNullable extends Migration
{
    public function up()
    {
        $driver = $this->db->getPlatform();
        if ($driver === 'MySQLi') {
            $this->setMysqlQuoteNullable(true);
        } else {
            $this->db->query('ALTER TABLE ' . $this->db->protectIdentifiers('payments', true) . ' ALTER COLUMN quote_id DROP NOT NULL');
        }
    }

    public function down()
    {
        $driver = $this->db->getPlatform();
        if ($driver === 'MySQLi') {
            $this->setMysqlQuoteNullable(false);
        } else {
            $this->db->query('ALTER TABLE ' . $this->db->protectIdentifiers('payments', true) . ' ALTER COLUMN quote_id SET NOT NULL');
        }
    }

    private function setMysqlQuoteNullable(bool $nullable): void
    {
        $payments = $this->db->protectIdentifiers('payments', true);
        // The legacy database can use a different collation for UUID columns.
        // Remove the optional FK only when present; recreating it after MODIFY
        // would fail on those installations and leave the migration half-done.
        $row = $this->db->query(
            'SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            ['payments', 'fk_payments_quote']
        )->getRowArray();
        if ((int) ($row['c'] ?? 0) > 0) {
            $this->db->query("ALTER TABLE {$payments} DROP FOREIGN KEY fk_payments_quote");
        }
        $this->db->query("ALTER TABLE {$payments} MODIFY COLUMN quote_id VARCHAR(36) " . ($nullable ? 'NULL' : 'NOT NULL'));
    }
}
