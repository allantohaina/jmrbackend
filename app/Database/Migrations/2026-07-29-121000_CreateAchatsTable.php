<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAchatsTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('achats')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'UUID',
                    'default' => new \CodeIgniter\Database\RawSql('gen_random_uuid()'),
                ],
                'fournisseur' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false,
                ],
                'categorie' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'montant' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'null' => false,
                    'default' => 0,
                ],
                'date_achat' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'statut' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'En attente',
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                ],
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('statut');
            $this->forge->createTable('achats');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('achats')) {
            $this->forge->dropTable('achats');
        }
    }
}
