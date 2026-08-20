<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDraftMetadataToQuotes extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('titre', 'quotes')) {
            $this->forge->addColumn('quotes', [
                'titre' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            ]);
        }
        if (!$this->db->fieldExists('progression', 'quotes')) {
            $this->forge->addColumn('quotes', [
                'progression' => ['type' => 'SMALLINT', 'default' => 0],
            ]);
        }
        $this->db->query('CREATE INDEX idx_quotes_status ON quotes(status, deleted_at)');
        $this->db->query('CREATE INDEX idx_quotes_updated ON quotes(updated_at)');
    }

    public function down()
    {
        $driver = strtolower($this->db->DBDriver);
        if (in_array($driver, ['mysqli', 'mysql'], true)) {
            $this->db->query('DROP INDEX idx_quotes_updated ON quotes');
            $this->db->query('DROP INDEX idx_quotes_status ON quotes');
        } else {
            $this->db->query('DROP INDEX idx_quotes_updated');
            $this->db->query('DROP INDEX idx_quotes_status');
        }
        $this->forge->dropColumn('quotes', ['progression', 'titre']);
    }
}