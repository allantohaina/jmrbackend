<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsPrivilegedToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'is_privileged' => ['type' => 'BOOLEAN', 'default' => false, 'after' => 'is_active'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'is_privileged');
    }
}
