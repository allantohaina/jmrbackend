<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR', 'constraint' => '36',
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'unique' => true,
            ],
            'password_hash' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'role' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'default' => 'user',
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'failed_login_count' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'locked_until' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'last_failed_login_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'last_login_at' => [
                'type' => 'TIMESTAMP',
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
        $this->forge->createTable('users');

    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
