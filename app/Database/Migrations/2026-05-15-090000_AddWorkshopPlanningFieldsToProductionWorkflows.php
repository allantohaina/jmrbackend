<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkshopPlanningFieldsToProductionWorkflows extends Migration
{
    use FieldHelpers;

    public function up()
    {
        $fields = [
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
            'styles' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'measurements' => $this->textField(true),
            'production_notes' => $this->textField(true),
        ];

        foreach ($fields as $name => $definition) {
            if (!$this->db->fieldExists($name, 'production_workflows')) {
                $this->forge->addColumn('production_workflows', [$name => $definition]);
            }
        }
    }

    public function down()
    {
        foreach ([
            'production_notes',
            'measurements',
            'styles',
            'launch_date',
            'delivery_date',
            'approval_date',
            'client_name',
            'workflow_type',
        ] as $name) {
            if ($this->db->fieldExists($name, 'production_workflows')) {
                $this->forge->dropColumn('production_workflows', $name);
            }
        }
    }
}
