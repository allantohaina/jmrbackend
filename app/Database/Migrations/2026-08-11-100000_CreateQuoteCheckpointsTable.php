<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;

class CreateQuoteCheckpointsTable extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if (!$this->db->tableExists('quote_checkpoints')) {
            $this->forge->addField([
                'id' => $this->uuidField(),
                'quote_id' => $this->uuidField(),
                'commande_id' => $this->uuidField(true),
                'title' => $this->varcharField(255),
                'description' => $this->textField(),
                'status' => $this->varcharField(50, false, 'upcoming'),
                'validated_at' => $this->timestampField(true),
                'validated_by' => $this->varcharField(255, true),
                'sort_order' => ['type' => 'INT', 'default' => 0],
                'created_at' => $this->timestampField(true),
                'updated_at' => $this->timestampField(true),
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('quote_id');
            $this->forge->addKey('commande_id');
            $this->forge->addForeignKey('quote_id', 'quotes', 'id', 'CASCADE', 'CASCADE', 'fk_checkpoints_quote');
            $this->forge->createTable('quote_checkpoints');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('quote_checkpoints')) {
            $this->forge->dropTable('quote_checkpoints');
        }
    }
}
