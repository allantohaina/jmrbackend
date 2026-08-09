<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;

class AddTissuCalculFieldsToCommandes extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if (!$this->db->tableExists('commandes')) {
            return;
        }

        $existingFields = array_keys($this->db->getFieldNames('commandes'));

        $fields = [
            'quantite_tissu_necessaire' => [
                'type' => 'DECIMAL',
                'constraint' => '15,3',
                'null' => true,
                'default' => 0,
            ],
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
        ];

        $toAdd = [];
        foreach ($fields as $name => $def) {
            if (!in_array($name, $existingFields, true)) {
                $toAdd[$name] = $def;
            }
        }

        if ($toAdd !== []) {
            $this->forge->addColumn('commandes', $toAdd);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('commandes')) {
            return;
        }

        $existingFields = array_keys($this->db->getFieldNames('commandes'));

        $dropFields = ['quantite_tissu_necessaire', 'conso_tissu_unitaire', 'taux_chute_pct'];
        $toDrop = array_values(array_intersect($dropFields, $existingFields));
        
        if ($toDrop !== []) {
            $this->forge->dropColumn('commandes', $toDrop);
        }
    }
}
