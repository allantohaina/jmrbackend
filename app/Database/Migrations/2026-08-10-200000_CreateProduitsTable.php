<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;

class CreateProduitsTable extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if (!$this->db->tableExists('produits')) {
            $this->forge->addField([
                'id' => $this->uuidField(),
                'nom' => $this->varcharField(255),
                'categorie' => $this->varcharField(100, true),
                'conso_tissu_unitaire' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,3',
                    'null' => false,
                    'default' => 0,
                ],
                'conso_tissu_par_taille' => $this->jsonbField(true),
                'niveau_difficulte_defaut' => [
                    'type' => 'DECIMAL',
                    'constraint' => '4,2',
                    'null' => false,
                    'default' => 1.00,
                ],
                'moq' => [
                    'type' => 'INTEGER',
                    'null' => false,
                    'default' => 1,
                ],
                'cout_matiere_defaut' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'null' => false,
                    'default' => 0,
                ],
                'cout_mo_par_piece' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'null' => false,
                    'default' => 0,
                ],
                'frais_generaux_pct' => [
                    'type' => 'DECIMAL',
                    'constraint' => '5,2',
                    'null' => false,
                    'default' => 20.00,
                ],
                'description' => $this->textField(true),
                'photo_url' => $this->varcharField(500, true),
                'created_at' => $this->timestampField(true),
                'updated_at' => $this->timestampField(true),
                'deleted_at' => $this->timestampField(true),
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('categorie');
            $this->forge->createTable('produits');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('produits')) {
            $this->forge->dropTable('produits');
        }
    }
}
