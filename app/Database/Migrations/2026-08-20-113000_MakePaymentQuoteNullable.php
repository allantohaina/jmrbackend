<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakePaymentQuoteNullable extends Migration
{
    public function up()
    {
        $driver = $this->db->getPlatform();
        if ($driver === 'MySQLi') {
            $this->db->query('ALTER TABLE ' . $this->db->protectIdentifiers('payments', true) . ' MODIFY COLUMN quote_id VARCHAR(36) NULL');
        } else {
            $this->db->query('ALTER TABLE ' . $this->db->protectIdentifiers('payments', true) . ' ALTER COLUMN quote_id DROP NOT NULL');
        }
    }

    public function down()
    {
        $driver = $this->db->getPlatform();
        if ($driver === 'MySQLi') {
            $this->db->query('ALTER TABLE ' . $this->db->protectIdentifiers('payments', true) . ' MODIFY COLUMN quote_id VARCHAR(36) NOT NULL');
        } else {
            $this->db->query('ALTER TABLE ' . $this->db->protectIdentifiers('payments', true) . ' ALTER COLUMN quote_id SET NOT NULL');
        }
    }
}