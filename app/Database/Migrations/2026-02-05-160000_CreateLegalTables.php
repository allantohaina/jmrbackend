<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalTables extends Migration
{
    use FieldHelpers;

    public function up()
    {
        $this->forge->addField([
            'id' => $this->uuidField(),
            'user_id' => $this->uuidField(true),
            'subject' => $this->varcharField(100),
            'version' => $this->varcharField(50),
            'granted' => $this->booleanField(false),
            'granted_at' => $this->timestampField(true),
            'revoked_at' => $this->timestampField(true),
            'ip_address' => $this->ipAddressField(),
            'user_agent' => $this->userAgentField(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('subject');
        $this->forge->createTable('user_consents', true);

        $this->forge->addField([
            'id' => $this->uuidField(),
            'user_id' => $this->uuidField(true),
            'email' => $this->varcharField(255, true),
            'request_type' => $this->varcharField(50),
            'status' => $this->varcharField(30, false, 'received'),
            'details' => $this->textField(),
            'created_at' => $this->timestampField(),
            'updated_at' => $this->timestampField(true),
            'completed_at' => $this->timestampField(true),
            'ip_address' => $this->ipAddressField(),
            'user_agent' => $this->userAgentField(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('request_type');
        $this->forge->createTable('data_requests', true);

        $this->forge->addField([
            'id' => $this->uuidField(),
            'email' => $this->varcharField(255),
            'reason' => $this->varcharField(255, true),
            'created_at' => $this->timestampField(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('email');
        $this->forge->createTable('email_unsubscribes', true);

    }

    public function down()
    {
        $this->forge->dropTable('email_unsubscribes', true);
        $this->forge->dropTable('data_requests', true);
        $this->forge->dropTable('user_consents', true);

    }
}
