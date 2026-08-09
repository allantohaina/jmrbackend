<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;

class CreateAttachmentsTable extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if (!$this->db->tableExists('attachments')) {
            $this->forge->addField([
                'id' => $this->uuidField(),
                'entity_type' => $this->varcharField(50),
                'entity_id' => $this->uuidField(),
                'original_name' => $this->varcharField(255),
                'stored_name' => $this->varcharField(255),
                'file_type' => $this->varcharField(50),
                'mime_type' => $this->varcharField(100, true),
                'file_size' => [
                    'type' => 'BIGINT',
                    'null' => true,
                ],
                'storage_path' => $this->varcharField(500, true),
                'url' => $this->varcharField(500, true),
                'uploaded_by' => $this->uuidField(true),
                'created_at' => $this->timestampField(true),
                'deleted_at' => $this->timestampField(true),
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['entity_type', 'entity_id']);
            $this->forge->addKey('uploaded_by');
            $this->forge->createTable('attachments');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('attachments')) {
            $this->forge->dropTable('attachments');
        }
    }
}
