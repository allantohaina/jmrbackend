<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkflowTestAccounts extends Migration
{
    public function up()
    {
        $accounts = [
            ['email' => 'mario.admin@jmrtextile.test', 'first_name' => 'Mario', 'last_name' => 'Admin', 'role' => 'admin'],
            ['email' => 'clara.client@jmrtextile.test', 'first_name' => 'Clara', 'last_name' => 'Client', 'role' => 'user'],
            ['email' => 'luc.worker@jmrtextile.test', 'first_name' => 'Luc', 'last_name' => 'Worker', 'role' => 'worker'],
        ];

        foreach ($accounts as $account) {
            if ($this->db->table('users')->where('email', $account['email'])->countAllResults() > 0) {
                continue;
            }

            $bytes = random_bytes(16);
            $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
            $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
            $id = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));

            $this->db->table('users')->insert([
                'id' => $id,
                ...$account,
                'password_hash' => password_hash('JmrTest!2026', PASSWORD_DEFAULT),
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
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
