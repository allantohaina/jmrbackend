<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDateLivraisonPrevueToQuotes extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE quotes ADD COLUMN date_livraison_prevue DATE NULL AFTER delai_souhaite");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE quotes DROP COLUMN date_livraison_prevue");
    }
}
