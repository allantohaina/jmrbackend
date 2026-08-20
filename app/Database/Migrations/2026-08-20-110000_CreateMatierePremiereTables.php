<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMatierePremiereTables extends Migration
{
    use FieldHelpers;

    public function up()
    {
        $this->forge->addField([
            'id' => $this->uuidField(),
            'nom' => $this->varcharField(255),
            'unite' => $this->varcharField(50, false, 'm'),
            'stock_actuel' => [
                'type' => 'DECIMAL',
                'constraint' => '15,3',
                'default' => '0.000',
            ],
            'stock_seuil' => [
                'type' => 'DECIMAL',
                'constraint' => '15,3',
                'default' => '0.000',
            ],
            'prix_unite' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => '0.00',
            ],
            'fournisseur' => $this->varcharField(255, true),
            'description' => $this->textField(true),
            'created_at' => $this->timestampField(true),
            'updated_at' => $this->timestampField(true),
            'deleted_at' => $this->timestampField(true),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('matieres');

        $this->forge->addField([
            'id' => $this->uuidField(),
            'matiere_id' => $this->uuidField(),
            'type' => $this->varcharField(20, false, 'entree'),
            'quantite' => [
                'type' => 'DECIMAL',
                'constraint' => '15,3',
            ],
            'motif' => $this->varcharField(255, true),
            'reference_type' => $this->varcharField(50, true),
            'reference_id' => $this->varcharField(36, true),
            'created_at' => $this->timestampField(true),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('matiere_id', 'matieres', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('mouvements_stock');
    }

    public function down()
    {
        $this->forge->dropTable('mouvements_stock', true);
        $this->forge->dropTable('matieres', true);
    }
}