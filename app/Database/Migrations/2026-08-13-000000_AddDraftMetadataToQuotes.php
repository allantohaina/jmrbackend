<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDraftMetadataToQuotes extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE quotes ADD COLUMN titre VARCHAR(255) NULL AFTER id");
        $this->db->query("ALTER TABLE quotes ADD COLUMN progression TINYINT UNSIGNED DEFAULT 0 COMMENT 'pourcentage 0-100' AFTER titre");
        $this->db->query("CREATE INDEX idx_quotes_status ON quotes(status, deleted_at)");
        $this->db->query("CREATE INDEX idx_quotes_updated ON quotes(updated_at)");
    }

    public function down()
    {
        $this->db->query("DROP INDEX idx_quotes_updated ON quotes");
        $this->db->query("DROP INDEX idx_quotes_status ON quotes");
        $this->db->query("ALTER TABLE quotes DROP COLUMN progression");
        $this->db->query("ALTER TABLE quotes DROP COLUMN titre");
    }
}