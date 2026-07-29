<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddForeignKeyConstraints extends Migration
{
    public function up()
    {
        // refresh_tokens.user_id -> users(id)
        if ($this->db->tableExists('refresh_tokens') && $this->db->tableExists('users')) {
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_refresh_tokens_user');
        }

        // user_consents.user_id -> users(id)
        if ($this->db->tableExists('user_consents') && $this->db->tableExists('users')) {
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_user_consents_user');
        }

        // data_requests.user_id -> users(id)
        if ($this->db->tableExists('data_requests') && $this->db->tableExists('users')) {
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_data_requests_user');
        }

        // audit_logs.actor_user_id -> users(id)
        if ($this->db->tableExists('audit_logs') && $this->db->tableExists('users')) {
            $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_audit_logs_actor');
        }

        // token_history.user_id -> users(id)
        if ($this->db->tableExists('token_history') && $this->db->tableExists('users')) {
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_token_history_user');
        }

        // order_project_history.project_id -> production_workflows(id) if it exists
        if ($this->db->tableExists('order_project_history') && $this->db->tableExists('production_workflows')) {
            $this->forge->addForeignKey('project_id', 'production_workflows', 'id', 'CASCADE', 'CASCADE', 'fk_order_history_project');
        }

        // order_project_history.actor_user_id -> users(id)
        if ($this->db->tableExists('order_project_history') && $this->db->tableExists('users')) {
            $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_order_history_actor');
        }

        // production_workflows.project_id -> no parent table, skip
        // production_checklists.project_id -> no parent table, skip
        // assemblages.project_id -> no parent table, skip
    }

    public function down()
    {
        $keys = [
            'fk_refresh_tokens_user',
            'fk_user_consents_user',
            'fk_data_requests_user',
            'fk_audit_logs_actor',
            'fk_token_history_user',
            'fk_order_history_project',
            'fk_order_history_actor',
        ];

        foreach ($keys as $key) {
            $this->forge->dropForeignKey('refresh_tokens', $key, true);
            $this->forge->dropForeignKey('user_consents', $key, true);
            $this->forge->dropForeignKey('data_requests', $key, true);
            $this->forge->dropForeignKey('audit_logs', $key, true);
            $this->forge->dropForeignKey('token_history', $key, true);
            $this->forge->dropForeignKey('order_project_history', $key, true);
            $this->forge->dropForeignKey('production_workflows', $key, true);
            $this->forge->dropForeignKey('production_checklists', $key, true);
            $this->forge->dropForeignKey('assemblages', $key, true);
        }
    }
}
