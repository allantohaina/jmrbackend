<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdminSignatureFields extends Migration
{
    use FieldHelpers;

    public function up()
    {
        $signatureFields = [
            'admin_signature_name' => $this->varcharField(255, true),
            'admin_signature_at'   => $this->timestampField(true),
        ];

        if ($this->db->tableExists('quotes')) {
            $fields = array_keys($this->db->getFieldNames('quotes'));
            if (!in_array('admin_signature_name', $fields, true)) {
                $this->forge->addColumn('quotes', $signatureFields);
            }
        }

        if ($this->db->tableExists('commandes')) {
            $fields = array_keys($this->db->getFieldNames('commandes'));
            if (!in_array('admin_signature_name', $fields, true)) {
                $this->forge->addColumn('commandes', $signatureFields);
            }
        }

        if ($this->db->tableExists('bons_livraison')) {
            $fields = array_keys($this->db->getFieldNames('bons_livraison'));
            if (!in_array('admin_signature_name', $fields, true)) {
                $this->forge->addColumn('bons_livraison', $signatureFields);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('quotes')) {
            $fields = array_keys($this->db->getFieldNames('quotes'));
            $dropFields = [];
            if (in_array('admin_signature_name', $fields, true)) {
                $dropFields[] = 'admin_signature_name';
            }
            if (in_array('admin_signature_at', $fields, true)) {
                $dropFields[] = 'admin_signature_at';
            }
            if ($dropFields !== []) {
                $this->forge->dropColumn('quotes', $dropFields);
            }
        }

        if ($this->db->tableExists('commandes')) {
            $fields = array_keys($this->db->getFieldNames('commandes'));
            $dropFields = [];
            if (in_array('admin_signature_name', $fields, true)) {
                $dropFields[] = 'admin_signature_name';
            }
            if (in_array('admin_signature_at', $fields, true)) {
                $dropFields[] = 'admin_signature_at';
            }
            if ($dropFields !== []) {
                $this->forge->dropColumn('commandes', $dropFields);
            }
        }

        if ($this->db->tableExists('bons_livraison')) {
            $fields = array_keys($this->db->getFieldNames('bons_livraison'));
            $dropFields = [];
            if (in_array('admin_signature_name', $fields, true)) {
                $dropFields[] = 'admin_signature_name';
            }
            if (in_array('admin_signature_at', $fields, true)) {
                $dropFields[] = 'admin_signature_at';
            }
            if ($dropFields !== []) {
                $this->forge->dropColumn('bons_livraison', $dropFields);
            }
        }
    }
}
