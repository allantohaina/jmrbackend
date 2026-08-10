<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuoteDraftsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'UUID',
            ],
            'client_id' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => true,
            ],
            'payload' => [
                'type' => 'JSON',
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('client_id');
        $this->forge->createTable('quote_drafts');
    }

    public function down()
    {
        $this->forge->dropTable('quote_drafts');
    }
}