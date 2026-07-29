<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBanAndBlacklistTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('user_bans')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'UUID',
                    'default' => new \CodeIgniter\Database\RawSql('gen_random_uuid()'),
                ],
                'user_id' => [
                    'type' => 'UUID',
                    'null' => false,
                ],
                'banned_by' => [
                    'type' => 'UUID',
                    'null' => true,
                ],
                'reason' => [
                    'type' => 'TEXT',
                    'null' => false,
                ],
                'expires_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_user_bans_user');
            $this->forge->addForeignKey('banned_by', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_user_bans_banned_by');
            $this->forge->createTable('user_bans');
        }

        if (!$this->db->tableExists('user_blacklist')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'UUID',
                    'default' => new \CodeIgniter\Database\RawSql('gen_random_uuid()'),
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
                'reason' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('email');
            $this->forge->addKey('ip_address');
            $this->forge->createTable('user_blacklist');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('user_blacklist')) {
            $this->forge->dropTable('user_blacklist');
        }
        if ($this->db->tableExists('user_bans')) {
            $this->forge->dropTable('user_bans');
        }
    }
}
