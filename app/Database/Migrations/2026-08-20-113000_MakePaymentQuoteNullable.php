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
        $quotes = $this->db->protectIdentifiers('quotes', true);
        // MySQL requires a foreign key to be removed before altering its column.
        $this->db->query("ALTER TABLE {$payments} DROP FOREIGN KEY fk_payments_quote");
        $this->db->query("ALTER TABLE {$payments} MODIFY COLUMN quote_id VARCHAR(36) " . ($nullable ? 'NULL' : 'NOT NULL'));
        $this->db->query("ALTER TABLE {$payments} ADD CONSTRAINT fk_payments_quote FOREIGN KEY (quote_id) REFERENCES {$quotes} (id) ON DELETE CASCADE ON UPDATE CASCADE");
    }
}
