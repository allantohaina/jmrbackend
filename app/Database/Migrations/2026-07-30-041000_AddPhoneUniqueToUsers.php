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
        $this->db->simpleQuery('DROP INDEX users_phone_uq');
    }
}
