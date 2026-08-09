<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBonLivraisonTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('bons_livraison')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'UUID',
                ],
                'commande_id' => [
                    'type' => 'UUID',
                    'null' => false,
                ],
                'numero' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'date_livraison' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'destinataire' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false,
                ],
                'articles' => [
                    'type' => 'JSON',
                    'null' => true,
                ],
                'statut' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'Préparé',
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
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('numero');
            $this->forge->addKey('commande_id');
            $this->forge->addForeignKey('commande_id', 'commandes', 'id', 'CASCADE', 'CASCADE', 'fk_bons_livraison_commande');
            $this->forge->createTable('bons_livraison');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('bons_livraison')) {
            $this->forge->dropTable('bons_livraison');
        }
    }
}
