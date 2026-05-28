<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductionWorkflowsTable extends Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'workflow_type' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
                'default' => 'production_plan',
            ],
            'client_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'approval_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'delivery_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'launch_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
                'default' => 'draft',
            ],
            'current_step_id' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => true,
            ],
            'last_validated_step_id' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => true,
            ],
            'styles' => $this->textField(false),
            'measurements' => $this->textField(true),
            'production_notes' => $this->textField(true),
            'steps' => $this->textField(false),
            'history' => $this->textField(false),
            'rollback_context' => $this->textField(true),
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
        $this->forge->addKey('workflow_type');
        $this->forge->addKey('client_name');
        $this->forge->addKey('approval_date');
        $this->forge->addKey('status');
        $this->forge->createTable('production_workflows');
    }

    public function down()
    {
        $this->forge->dropTable('production_workflows');
    }
}
