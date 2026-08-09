<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;

class AddCotationFieldsToQuotes extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if (!$this->db->tableExists('quotes')) {
            return;
        }

        $existingFields = array_keys($this->db->getFieldNames('quotes'));

        $fields = [
            'client_id' => $this->uuidField(true),
            'produit_id' => $this->uuidField(true),
            'matiere_fournie_par' => $this->varcharField(20, true, 'atelier'),
            'conso_tissu_unitaire' => [
                'type' => 'DECIMAL',
                'constraint' => '10,3',
                'null' => true,
                'default' => 0,
            ],
            'taux_chute_pct' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'default' => 10.00,
            ],
            'niveau_difficulte' => [
                'type' => 'DECIMAL',
                'constraint' => '4,2',
                'null' => true,
                'default' => 1.00,
            ],
            'prix_unitaire_calcule' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0,
            ],
            'quantite_commandee' => [
                'type' => 'INTEGER',
                'null' => true,
                'default' => 0,
            ],
            'prix_total_calcule' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0,
            ],
            'cout_matiere' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0,
            ],
            'cout_main_oeuvre' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0,
            ],
            'cout_frais_generaux' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0,
            ],
            'frais_generaux_pct' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'default' => 20.00,
            ],
            'prix_matiere_par_metre' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0,
            ],
            'cout_mo_par_piece' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0,
            ],
        ];

        $toAdd = [];
        foreach ($fields as $name => $def) {
            if (!in_array($name, $existingFields, true)) {
                $toAdd[$name] = $def;
            }
        }

        if ($toAdd !== []) {
            $this->forge->addColumn('quotes', $toAdd);
        }

        if (!in_array('client_id', $existingFields, true) && $this->db->tableExists('users')) {
            $this->forge->addForeignKey('client_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_quotes_client_user');
        }
        if (!in_array('produit_id', $existingFields, true) && $this->db->tableExists('produits')) {
            $this->forge->addForeignKey('produit_id', 'produits', 'id', 'SET NULL', 'CASCADE', 'fk_quotes_produit');
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('quotes')) {
            return;
        }

        $existingFields = array_keys($this->db->getFieldNames('quotes'));

        $dropKeys = ['fk_quotes_client_user', 'fk_quotes_produit'];
        foreach ($dropKeys as $key) {
            $this->forge->dropForeignKey('quotes', $key, true);
        }

        $dropFields = [
            'client_id', 'produit_id', 'matiere_fournie_par',
            'conso_tissu_unitaire', 'taux_chute_pct', 'niveau_difficulte',
            'prix_unitaire_calcule', 'quantite_commandee', 'prix_total_calcule',
            'cout_matiere', 'cout_main_oeuvre', 'cout_frais_generaux',
            'frais_generaux_pct', 'prix_matiere_par_metre', 'cout_mo_par_piece',
        ];

        $toDrop = array_values(array_intersect($dropFields, $existingFields));
        if ($toDrop !== []) {
            $this->forge->dropColumn('quotes', $toDrop);
        }
    }
}
