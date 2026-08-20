<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAvisProduits extends Migration
{
    use FieldHelpers;

    public function up()
    {
        $this->forge->addField([
            'id' => $this->uuidField(),
            'produit_id' => $this->uuidFkField(),
            'user_id' => $this->uuidFkField(true),
            'note' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'commentaire' => $this->textField(true),
            'statut' => $this->varcharField(20, false, 'pending'),
            'created_at' => $this->timestampField(true),
            'updated_at' => $this->timestampField(true),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('produit_id', 'produits', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('avis_produits');
    }

    public function down()
    {
        $this->forge->dropTable('avis_produits', true);
    }
}