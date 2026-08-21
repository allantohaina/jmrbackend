<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteContentVersionsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('site_content_versions')) return;

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'snapshot' => ['type' => 'LONGTEXT', 'null' => false],
            'author_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'author_name' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('site_content_versions');
    }

    public function down()
    {
        if ($this->db->tableExists('site_content_versions')) $this->forge->dropTable('site_content_versions');
    }
}
