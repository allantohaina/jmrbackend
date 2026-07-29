<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentsTable extends Migration
{
    /** Payments are tracked via quotes.deposit_paid / quotes.balance_paid. */
    public function up()
    {
        // Intentionally empty — payments are boolean fields on the quotes table.
    }

    public function down()
    {
        // Intentionally empty — see up().
    }
}
