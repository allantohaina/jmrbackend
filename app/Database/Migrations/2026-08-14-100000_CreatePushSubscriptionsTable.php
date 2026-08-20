<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;

class CreatePushSubscriptionsTable extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if (!$this->db->tableExists('push_subscriptions')) {
            $this->forge->addField([
                'id' => $this->uuidField(),
                'user_id' => $this->uuidField(),
                'endpoint' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                ],
                'keys_p256dh' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'keys_auth' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'created_at' => $this->timestampField(true),
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['user_id', 'endpoint']);
            $this->forge->createTable('push_subscriptions');

            $this->addForeignKeySafely();
        }
    }

    private function addForeignKeySafely(): void
    {
        $driver = strtolower($this->db->DBDriver);
        $tbl = $this->db->protectIdentifiers('push_subscriptions');
        $col = $this->db->protectIdentifiers('user_id');
        $refTbl = $this->db->protectIdentifiers('users');
        $refCol = $this->db->protectIdentifiers('id');
        $fkName = 'fk_push_subscriptions_user';

        if ($driver === 'mysqli') {
            $check = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ?";
        } elseif ($driver === 'postgre') {
            $check = "SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = current_schema() AND constraint_name = ?";
        } else {
            $check = "SELECT COUNT(*) FROM user_constraints WHERE constraint_name = ?";
            $fkName = strtoupper($fkName);
        }

        $row = $this->db->query($check, [$fkName])->getRowArray();
        $exists = is_array($row) && (int) array_values($row)[0] > 0;
        if ($exists) return;

        $cleanup = "DELETE FROM {$tbl} WHERE {$col} NOT IN (SELECT {$refCol} FROM {$refTbl})";
        $this->db->query($cleanup);

        $sql = "ALTER TABLE {$tbl} ADD CONSTRAINT {$fkName} FOREIGN KEY ({$col}) REFERENCES {$refTbl} ({$refCol}) ON DELETE CASCADE ON UPDATE CASCADE";
        $this->db->query($sql);
    }

    public function down()
    {
        if ($this->db->tableExists('push_subscriptions')) {
            $this->forge->dropTable('push_subscriptions');
        }
    }
}