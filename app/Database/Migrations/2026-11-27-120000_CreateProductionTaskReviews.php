<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductionTaskReviews extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('production_task_reviews')) return;

        $this->forge->addField([
            'id' => ['type' => 'VARCHAR', 'constraint' => '36'],
            'task_id' => ['type' => 'VARCHAR', 'constraint' => '36'],
            'quote_id' => ['type' => 'VARCHAR', 'constraint' => '36', 'null' => true],
            'reviewer_id' => ['type' => 'VARCHAR', 'constraint' => '36', 'null' => true],
            'quality_score' => ['type' => 'INT', 'null' => true],
            'feedback' => ['type' => 'TEXT', 'null' => true],
            'delay_reason' => ['type' => 'TEXT', 'null' => true],
            'contretemps' => ['type' => 'BOOLEAN', 'default' => false],
            'hours_estimated' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'hours_final' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP'],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id');
        $this->forge->createTable('production_task_reviews');
    }

    public function down(): void
    {
        $this->forge->dropTable('production_task_reviews', true);
    }
}
