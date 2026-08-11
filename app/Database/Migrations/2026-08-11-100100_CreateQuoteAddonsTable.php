<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;

class CreateQuoteAddonsTable extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if (!$this->db->tableExists('quote_addons')) {
            $this->forge->addField([
                'id' => $this->uuidField(),
                'quote_id' => $this->uuidField(),
                'commande_id' => $this->uuidField(true),
                'title' => $this->varcharField(255),
                'description' => $this->textField(),
                'price' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
                'status' => $this->varcharField(50, false, 'pending'),
                'created_at' => $this->timestampField(true),
                'updated_at' => $this->timestampField(true),
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('quote_id');
            $this->forge->addKey('commande_id');
            $this->forge->addForeignKey('quote_id', 'quotes', 'id', 'CASCADE', 'CASCADE', 'fk_addons_quote');
            $this->forge->createTable('quote_addons');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('quote_addons')) {
            $this->forge->dropTable('quote_addons');
        }
    }
}
