<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDateLivraisonPrevueToQuotes extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('date_livraison_prevue', 'quotes')) {
            $this->forge->addColumn('quotes', [
                'date_livraison_prevue' => ['type' => 'DATE', 'null' => true],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('quotes', 'date_livraison_prevue');
    }
}
