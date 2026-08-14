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
                    'constraint' => 1000,
                ],
                'keys_p256dh' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                ],
                'keys_auth' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                ],
                'created_at' => $this->timestampField(true),
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['user_id', 'endpoint']);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_push_subscriptions_user');
            $this->forge->createTable('push_subscriptions');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('push_subscriptions')) {
            $this->forge->dropTable('push_subscriptions');
        }
    }
}