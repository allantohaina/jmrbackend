<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
class CreateLegalTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'UUID',
                'null' => false,
            ],
            'user_id' => [
                'type' => 'UUID',
                'null' => true,
            ],
            'subject' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'version' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'granted' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'granted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'revoked_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('subject');
        $this->forge->createTable('user_consents', true);

        $this->forge->addField([
            'id' => [
                'type' => 'UUID',
                'null' => false,
            ],
            'user_id' => [
                'type' => 'UUID',
                'null' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'request_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'received',
            ],
            'details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('request_type');
        $this->forge->createTable('data_requests', true);

        $this->forge->addField([
            'id' => [
                'type' => 'UUID',
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'reason' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('email');
        $this->forge->createTable('email_unsubscribes', true);

    }

    public function down()
    {
        $this->forge->dropTable('email_unsubscribes', true);
        $this->forge->dropTable('data_requests', true);
        $this->forge->dropTable('user_consents', true);

    }
}
