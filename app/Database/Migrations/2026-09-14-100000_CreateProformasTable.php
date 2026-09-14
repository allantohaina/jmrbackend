<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProformasTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('proformas')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'VARCHAR', 'constraint' => '36'],
            'number' => ['type' => 'VARCHAR', 'constraint' => '32'],
            'seq' => ['type' => 'INT', 'default' => 0],
            'year' => ['type' => 'INT', 'default' => 2026],
            'status' => ['type' => 'VARCHAR', 'constraint' => '30', 'default' => 'draft'],
            'quote_id' => ['type' => 'VARCHAR', 'constraint' => '36', 'null' => true],
            'client_id' => ['type' => 'VARCHAR', 'constraint' => '36', 'null' => true],
            'client_name' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => false],
            'client_email' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'client_phone' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'client_address' => ['type' => 'TEXT', 'null' => true],
            'client_tax_id' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'currency' => ['type' => 'VARCHAR', 'constraint' => '10', 'default' => 'MGA'],
            'tax_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 20],
            'discount_pct' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'deposit_pct' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 50],
            'lines' => ['type' => 'TEXT', 'null' => true],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'deposit_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'balance_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'issue_date' => ['type' => 'DATE', 'null' => true],
            'valid_until' => ['type' => 'DATE', 'null' => true],
            'delivery_address' => ['type' => 'TEXT', 'null' => true],
            'order_reference' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'payment_terms' => ['type' => 'TEXT', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'admin_signature_name' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'admin_signature_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'created_by' => ['type' => 'VARCHAR', 'constraint' => '36', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'deleted_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('number', false, true);
        $this->forge->addKey(['year', 'seq']);
        $this->forge->addKey('status');
        $this->forge->addKey('quote_id');
        $this->forge->createTable('proformas');
    }

    public function down()
    {
        if ($this->db->tableExists('proformas')) {
            $this->forge->dropTable('proformas');
        }
    }
}
