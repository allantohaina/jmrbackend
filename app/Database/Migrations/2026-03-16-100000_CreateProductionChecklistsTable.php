<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductionChecklistsTable extends Migration
{
    use FieldHelpers;

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
                'null' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'pending',
            ],
            'items' => [
                'type' => 'TEXT', // Use TEXT for JSON since JSONB might not be available everywhere
                'null' => false,
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
        $this->forge->addKey('type');
        $this->forge->createTable('production_checklists');
    }

    public function down()
    {
        $this->forge->dropTable('production_checklists');
    }
}
