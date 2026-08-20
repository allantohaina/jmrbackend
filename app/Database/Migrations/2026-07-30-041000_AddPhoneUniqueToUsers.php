<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPhoneUniqueToUsers extends Migration
{
    public function up()
    {
        $this->db->simpleQuery('CREATE UNIQUE INDEX users_phone_uq ON users (phone)');
    }

    public function down()
    {
        if (strtolower((string) $this->db->DBDriver) === 'mysqli') {
            $this->db->simpleQuery('ALTER TABLE users DROP INDEX users_phone_uq');
        } else {
            $this->db->simpleQuery('DROP INDEX users_phone_uq');
        }
    }
}
