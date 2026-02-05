<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $model = new UserModel();

        $model->insert([
            'email' => 'admin@jmrtextile.com',
            'password' => 'etherion',
            'first_name' => 'Admin',
            'last_name' => 'JMR Textile',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $model->insert([
            'email' => 'user@jmrtextile.com',
            'password' => 'etherion',
            'first_name' => 'User',
            'last_name' => 'JMR Textile',
            'role' => 'user',
            'is_active' => true,
        ]);

        $model->insert([
            'email' => 'inactive@jmrtextile.com',
            'password' => 'etherion',
            'first_name' => 'Inactive',
            'last_name' => 'JMR Textile',
            'role' => 'user',
            'is_active' => false,
        ]);
    }
}
