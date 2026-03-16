<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssemblagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'project_id' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => 'pending',
            ],
            'details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->createTable('assemblages');
    }

    public function down()
    {
        $this->forge->dropTable('assemblages');
    }
}
