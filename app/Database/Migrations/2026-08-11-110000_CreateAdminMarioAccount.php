<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminMarioAccount extends Migration
{
    public function up()
    {
        $hash = password_hash('P@Z}uR9p;@3{qywNs', PASSWORD_DEFAULT);

        $this->db->table('users')->insert([
            'id'            => $this->uuidV4(),
            'email'         => 'mario@jmrtextile.com',
            'password_hash' => $hash,
            'first_name'    => 'Mario',
            'last_name'     => 'Admin',
            'role'          => 'admin',
            'is_active'     => true,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->db->table('users')->where('email', 'mario@jmrtextile.com')->delete();
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
