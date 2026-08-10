<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddConfirmationDeadlineToQuotes extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE quotes ADD COLUMN confirmation_deadline DATETIME NULL AFTER status");
        $this->db->query("ALTER TABLE quotes ADD COLUMN confirmation_days INT DEFAULT 7 AFTER confirmation_deadline");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE quotes DROP COLUMN confirmation_deadline");
        $this->db->query("ALTER TABLE quotes DROP COLUMN confirmation_days");
    }
}
