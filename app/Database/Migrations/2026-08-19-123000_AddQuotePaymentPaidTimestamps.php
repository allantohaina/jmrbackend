<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuotePaymentPaidTimestamps extends Migration
{
    public function up()
    {
        $this->forge->addColumn('quotes', [
            'deposit_paid_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'balance_paid_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('quotes', ['deposit_paid_at', 'balance_paid_at']);
    }
}