<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIpBlocklistTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('ip_blocklist')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'UUID',
                    'default' => new \CodeIgniter\Database\RawSql('gen_random_uuid()'),
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                    'null' => false,
                ],
                'reason' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'failed_attempts' => [
                    'type' => 'INT',
                    'default' => 1,
                ],
                'blocked_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                ],
                'expires_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
                'is_active' => [
                    'type' => 'BOOLEAN',
                    'default' => true,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('ip_address');
            $this->forge->addKey('ip_address');
            $this->forge->createTable('ip_blocklist');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('ip_blocklist')) {
            $this->forge->dropTable('ip_blocklist');
        }
    }
}
