<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkerFieldsToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'profile_image' => ['type' => 'VARCHAR', 'constraint' => '500', 'null' => true, 'after' => 'address'],
            'department' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'profile_image'],
            'position' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'department'],
            'hire_date' => ['type' => 'DATE', 'null' => true, 'after' => 'position'],
            'cin' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true, 'after' => 'hire_date'],
            'documents' => ['type' => 'TEXT', 'null' => true, 'after' => 'cin'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', [
            'profile_image',
            'department',
            'position',
            'hire_date',
            'cin',
            'documents',
        ]);
    }
}
