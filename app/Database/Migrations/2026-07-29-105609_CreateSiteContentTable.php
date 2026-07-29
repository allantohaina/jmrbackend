<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteContentTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('site_content')) {
            $this->forge->addField([
                'key' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => false,
                ],
                'value' => [
                    'type' => 'TEXT',
                    'null' => false,
                    'default' => '',
                ],
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                ],
            ]);
            $this->forge->addKey('key', true);
            $this->forge->createTable('site_content');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('site_content')) {
            $this->forge->dropTable('site_content');
        }
    }
}
