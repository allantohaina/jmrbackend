<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkflowTestAccounts extends Migration
{
    public function up()
    {
        // Comptes de test supprimés : cette migration ne crée plus de comptes.
        // down() permet de purger les comptes déjà créés sur les bases existantes.
    }

    public function down()
    {
        $this->db->table('users')->whereIn('email', [
            'mario.admin@jmrtextile.test',
            'clara.client@jmrtextile.test',
            'luc.worker@jmrtextile.test',
        ])->delete();
    }
}
