<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteContentsTable extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if ($this->db->tableExists('site_contents')) {
            return;
        }

        $this->forge->addField([
            'id'          => $this->uuidField(),
            'content_key' => $this->varcharField(191),
            'locale'      => $this->varcharField(5, false, 'fr'),
            'type'        => $this->varcharField(20),
            'value'       => $this->textField(false),
            'created_at'  => $this->timestampField(true),
            'updated_at'  => $this->timestampField(true),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['content_key', 'locale']);
        $this->forge->createTable('site_contents');
    }

    public function down()
    {
        $this->forge->dropTable('site_contents', true);
    }
}
