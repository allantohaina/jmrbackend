<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;

class CreateDemandesClientTable extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if (!$this->db->tableExists('demandes_client')) {
            $this->forge->addField([
                'id' => $this->uuidField(),
                'nom_client' => $this->varcharField(255),
                'entreprise' => $this->varcharField(255, true),
                'email' => $this->varcharField(255, true),
                'telephone' => $this->varcharField(50, true),
                'description' => $this->textField(false),
                'statut' => $this->varcharField(50, false, 'Nouvelle'),
                'cotation_id' => $this->uuidField(true),
                'date_reception' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'created_at' => $this->timestampField(true),
                'updated_at' => $this->timestampField(true),
                'deleted_at' => $this->timestampField(true),
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('statut');
            $this->forge->addKey('cotation_id');
            $this->forge->addForeignKey('cotation_id', 'quotes', 'id', 'SET NULL', 'CASCADE', 'fk_demandes_cotation');
            $this->forge->createTable('demandes_client');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('demandes_client')) {
            $keys = ['fk_demandes_cotation'];
            foreach ($keys as $key) {
                $this->forge->dropForeignKey('demandes_client', $key, true);
            }
            $this->forge->dropTable('demandes_client');
        }
    }
}
