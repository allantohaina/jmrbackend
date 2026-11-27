<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRetouchesTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('retouches')) return;
        $this->forge->addField([
            'id' => ['type' => 'VARCHAR', 'constraint' => '36'],
            'quote_id' => ['type' => 'VARCHAR', 'constraint' => '36'],
            'commande_id' => ['type' => 'VARCHAR', 'constraint' => '36', 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => '255'],
            'description' => ['type' => 'TEXT', 'null' => true],
            'price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => '20', 'default' => 'pending'],
            'created_by' => ['type' => 'VARCHAR', 'constraint' => '36', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP'],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('quote_id');
        $this->forge->createTable('retouches');
    }

    public function down(): void
    {
        $this->forge->dropTable('retouches', true);
    }
}
