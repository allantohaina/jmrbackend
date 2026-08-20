<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentSubmissionFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('payments', [
            'payment_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'transaction_ref' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('payments', ['payment_type', 'transaction_ref']);
    }
}