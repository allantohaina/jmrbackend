<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogs extends Migration
{
    use FieldHelpers;

    public function up()
    {
        $this->forge->addField([
            'id' => $this->uuidField(),
            'actor_user_id' => $this->uuidField(true),
            'action' => $this->varcharField(50),
            'entity_type' => $this->varcharField(50),
            'entity_id' => $this->uuidField(true),
            'before_data' => $this->jsonbField(),
            'after_data' => $this->jsonbField(),
            'ip_address' => $this->ipAddressField(),
            'user_agent' => $this->userAgentField(),
            'created_at' => $this->timestampField(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('actor_user_id');
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->createTable('audit_logs', true);

        $this->forge->addField([
            'id' => $this->uuidField(),
            'user_id' => $this->uuidField(true),
            'action' => $this->varcharField(50),
            'jti' => $this->varcharField(36, true),
            'refresh_token_id' => $this->uuidField(true),
            'meta' => $this->jsonbField(),
            'ip_address' => $this->ipAddressField(),
            'user_agent' => $this->userAgentField(),
            'created_at' => $this->timestampField(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('action');
        $this->forge->createTable('token_history', true);

        $this->forge->addField([
            'id' => $this->uuidField(),
            'project_id' => $this->uuidField(true),
            'status' => $this->varcharField(50, true),
            'action' => $this->varcharField(50),
            'details' => $this->jsonbField(),
            'actor_user_id' => $this->uuidField(true),
            'created_at' => $this->timestampField(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->addKey('action');
        $this->forge->createTable('order_project_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('order_project_history', true);
        $this->forge->dropTable('token_history', true);
        $this->forge->dropTable('audit_logs', true);
    }
}
