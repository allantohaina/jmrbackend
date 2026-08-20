<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLiensPaiementEtPointsFidelite extends Migration
{
    use FieldHelpers;

    public function up()
    {
        $this->forge->addField([
            'id' => $this->uuidField(),
            'commande_id' => $this->uuidFkField(),
            'token' => $this->varcharField(64),
            'montant' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'statut' => $this->varcharField(20, false, 'pending'),
            'expire_at' => $this->timestampField(true),
            'paid_at' => $this->timestampField(true),
            'created_at' => $this->timestampField(true),
            'updated_at' => $this->timestampField(true),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('token', false, true);
        $this->forge->addForeignKey('commande_id', 'commandes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('liens_paiement');

        $this->forge->addField([
            'id' => $this->uuidField(),
            'user_id' => $this->uuidFkField(),
            'points' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'motif' => $this->varcharField(100),
            'reference_type' => $this->varcharField(50, true),
            'reference_id' => $this->varcharField(36, true),
            'created_at' => $this->timestampField(true),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('points_fidelite');
    }

    public function down()
    {
        $this->forge->dropTable('points_fidelite', true);
        $this->forge->dropTable('liens_paiement', true);
    }
}