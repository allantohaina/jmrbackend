<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompanyToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('users')) {
            return;
        }

        if ($this->db->fieldExists('company', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'company' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'last_name',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('users') && $this->db->fieldExists('company', 'users')) {
            $this->forge->dropColumn('users', 'company');
        }
    }
}
