<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuotesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR', 'constraint' => '36',
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'tissu' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'coupe' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'gabarit' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'style' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'grammage' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'tailles' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'quantite' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'finitions' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'delai_souhaite' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'request_type' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'default' => 'new',
            ],
            'modify_code' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'default' => 'pending',
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'deposit_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'balance_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'deposit_paid' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'balance_paid' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'files' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'notifications' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('quotes');
    }

    public function down()
    {
        $this->forge->dropTable('quotes');
    }
}
