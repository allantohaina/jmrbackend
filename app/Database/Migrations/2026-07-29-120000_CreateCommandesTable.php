<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCommandesTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('commandes')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'VARCHAR', 'constraint' => '36',
                ],
                'cotation_id' => [
                    'type' => 'VARCHAR', 'constraint' => '36',
                    'null' => true,
                ],
                'client_id' => [
                    'type' => 'VARCHAR', 'constraint' => '36',
                    'null' => false,
                ],
                'numero' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'designation' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'quantite' => [
                    'type' => 'INTEGER',
                    'null' => false,
                    'default' => 0,
                ],
                'prix_unitaire' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'null' => false,
                    'default' => 0,
                ],
                'total' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'null' => false,
                    'default' => 0,
                ],
                'statut_production' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                    'default' => 'En attente matière',
                ],
                'pieces_produites' => [
                    'type' => 'INTEGER',
                    'null' => false,
                    'default' => 0,
                ],
                'date_commande' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'date_livraison_prevue' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'date_livraison_reelle' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'notes' => [
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
                'deleted_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('numero');
            $this->forge->addKey('client_id');
            $this->forge->addKey('statut_production');
            $this->forge->addForeignKey('client_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_commandes_client');
            $this->forge->addForeignKey('cotation_id', 'quotes', 'id', 'SET NULL', 'CASCADE', 'fk_commandes_cotation');
            $this->forge->createTable('commandes');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('commandes')) {
            $this->forge->dropTable('commandes');
        }
    }
}
