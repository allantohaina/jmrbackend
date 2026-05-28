<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLoginTrackingToUsers extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        if (!$this->db->fieldExists('failed_login_count', 'users')) {
            $this->forge->addColumn('users', [
                'failed_login_count' => [
                    'type' => 'INT',
                    'default' => 0,
                    'null' => false,
                ],
            ]);
        }

        if (!$this->db->fieldExists('locked_until', 'users')) {
            $this->forge->addColumn('users', [
                'locked_until' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ]);
        }

        if (!$this->db->fieldExists('last_failed_login_at', 'users')) {
            $this->forge->addColumn('users', [
                'last_failed_login_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ]);
        }

        if (!$this->db->fieldExists('last_login_at', 'users')) {
            $this->forge->addColumn('users', [
                'last_login_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('failed_login_count', 'users')) {
            $this->forge->dropColumn('users', 'failed_login_count');
        }

        if ($this->db->fieldExists('locked_until', 'users')) {
            $this->forge->dropColumn('users', 'locked_until');
        }

        if ($this->db->fieldExists('last_failed_login_at', 'users')) {
            $this->forge->dropColumn('users', 'last_failed_login_at');
        }

        if ($this->db->fieldExists('last_login_at', 'users')) {
            $this->forge->dropColumn('users', 'last_login_at');
        }
    }
}
