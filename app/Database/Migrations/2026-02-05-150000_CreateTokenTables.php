<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
class CreateTokenTables extends Migration
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
                'null' => false,
            ],
            'token_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'expires_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'revoked_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'replaced_by' => [
                'type' => 'UUID',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
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
        $this->forge->addUniqueKey('token_hash');
        $this->forge->createTable('refresh_tokens', true);

        $this->forge->addField([
            'id' => [
                'type' => 'UUID',
                'null' => false,
            ],
            'jti' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'expires_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'revoked_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'reason' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('jti');
        $this->forge->createTable('token_blacklist', true);

    }

    public function down()
    {
        $this->forge->dropTable('token_blacklist', true);
        $this->forge->dropTable('refresh_tokens', true);

    }
}
