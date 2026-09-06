<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;

class CreateDocumentsTable extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if (!$this->db->tableExists('documents')) {
            $this->forge->addField([
                'id' => $this->uuidField(),
                'client_id' => $this->uuidFkField(),
                'devis_id' => $this->uuidFkField(true),
                'type' => $this->varcharField(50),
                'nom_original' => $this->varcharField(255),
                'chemin_stocke' => $this->varcharField(500),
                'mime_type' => $this->varcharField(255),
                'taille_bytes' => [
                    'type' => 'BIGINT',
                    'null' => false,
                ],
                'uploaded_by' => $this->uuidFkField(),
                'created_at' => $this->timestampField(true),
                'deleted_at' => $this->timestampField(true),
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('client_id');
            $this->forge->addKey('devis_id');
            $this->forge->addKey('type');
            $this->forge->addForeignKey('client_id', 'users', 'id', 'RESTRICT', 'CASCADE', 'fk_documents_client');
            $this->forge->addForeignKey('devis_id', 'quotes', 'id', 'SET NULL', 'CASCADE', 'fk_documents_devis');
            $this->forge->addForeignKey('uploaded_by', 'users', 'id', 'RESTRICT', 'CASCADE', 'fk_documents_uploader');
            $this->forge->createTable('documents');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('documents')) {
            foreach (['fk_documents_client', 'fk_documents_devis', 'fk_documents_uploader'] as $key) {
                $this->forge->dropForeignKey('documents', $key, true);
            }
            $this->forge->dropTable('documents');
        }
    }
}
