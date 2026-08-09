<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SetAdminAndWorkerRoles extends Migration
{
    public function up()
    {
        $this->db->table('users')->where('email', 'admin@jmrtextile.com')->update(['role' => 'admin']);
        $this->db->table('users')->where('email', 'worker@jmrtextile.com')->update(['role' => 'worker']);
    }

    public function down()
    {
        $this->db->table('users')->where('email', 'admin@jmrtextile.com')->update(['role' => 'user']);
        $this->db->table('users')->where('email', 'worker@jmrtextile.com')->update(['role' => 'user']);
    }
}
