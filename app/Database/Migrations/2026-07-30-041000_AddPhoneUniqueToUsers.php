<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPhoneUniqueToUsers extends Migration
{
    public function up()
    {
        $this->db->simpleQuery('CREATE UNIQUE INDEX IF NOT EXISTS users_phone_uq ON users (phone) WHERE phone IS NOT NULL');
    }

    public function down()
    {
        $this->db->simpleQuery('DROP INDEX IF EXISTS users_phone_uq');
    }
}
