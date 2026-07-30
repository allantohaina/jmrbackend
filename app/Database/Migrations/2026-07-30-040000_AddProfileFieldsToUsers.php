<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfileFieldsToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'birth_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'phone',
            ],
            'country' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'after' => 'birth_date',
            ],
            'address' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'country',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['birth_date', 'country', 'address']);
    }
}
