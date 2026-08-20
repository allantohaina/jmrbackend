<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddConfirmationDeadlineToQuotes extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('confirmation_deadline', 'quotes')) {
            $this->forge->addColumn('quotes', [
                'confirmation_deadline' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
        }
        if (!$this->db->fieldExists('confirmation_days', 'quotes')) {
            $this->forge->addColumn('quotes', [
                'confirmation_days' => ['type' => 'INT', 'default' => 7],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('quotes', ['confirmation_deadline', 'confirmation_days']);
    }
}
