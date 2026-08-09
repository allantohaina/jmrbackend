<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderCollaborationTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('payments')) {
            $this->forge->addField([
                'id' => ['type' => 'UUID'], 'quote_id' => ['type' => 'UUID', 'null' => false], 'commande_id' => ['type' => 'UUID', 'null' => true],
                'phase' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false], 'amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'submitted'], 'proof_path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'submitted_by' => ['type' => 'UUID', 'null' => true], 'reviewed_by' => ['type' => 'UUID', 'null' => true], 'review_note' => ['type' => 'TEXT', 'null' => true],
                'reviewed_at' => ['type' => 'TIMESTAMP', 'null' => true], 'created_at' => ['type' => 'TIMESTAMP', 'null' => false], 'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addKey(['quote_id', 'phase']); $this->forge->addKey(['commande_id', 'status']);
            $this->forge->addForeignKey('quote_id', 'quotes', 'id', 'CASCADE', 'CASCADE', 'fk_payments_quote');
            $this->forge->addForeignKey('commande_id', 'commandes', 'id', 'SET NULL', 'CASCADE', 'fk_payments_commande');
            $this->forge->createTable('payments');
        }

        if (!$this->db->tableExists('notifications')) {
            $this->forge->addField([
                'id' => ['type' => 'UUID'], 'recipient_user_id' => ['type' => 'UUID', 'null' => false],
                'actor_user_id' => ['type' => 'UUID', 'null' => true], 'entity_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
                'entity_id' => ['type' => 'UUID', 'null' => true], 'event' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
                'type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'info'],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false], 'message' => ['type' => 'TEXT', 'null' => false],
                'action_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true], 'read_at' => ['type' => 'TIMESTAMP', 'null' => true],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => false],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['recipient_user_id', 'read_at', 'created_at']);
            $this->forge->addForeignKey('recipient_user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_notifications_recipient');
            $this->forge->createTable('notifications');
        }

        if (!$this->db->tableExists('production_tickets')) {
            $this->forge->addField([
                'id' => ['type' => 'UUID'], 'commande_id' => ['type' => 'UUID', 'null' => false], 'quote_id' => ['type' => 'UUID', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'open'], 'changes_locked_at' => ['type' => 'TIMESTAMP', 'null' => true],
                'created_by' => ['type' => 'UUID', 'null' => true], 'closed_at' => ['type' => 'TIMESTAMP', 'null' => true],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => false], 'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey('commande_id');
            $this->forge->addForeignKey('commande_id', 'commandes', 'id', 'CASCADE', 'CASCADE', 'fk_tickets_commande');
            $this->forge->addForeignKey('quote_id', 'quotes', 'id', 'SET NULL', 'CASCADE', 'fk_tickets_quote');
            $this->forge->createTable('production_tickets');
        }

        if (!$this->db->tableExists('production_tasks')) {
            $this->forge->addField([
                'id' => ['type' => 'UUID'], 'ticket_id' => ['type' => 'UUID', 'null' => false], 'assigned_worker_id' => ['type' => 'UUID', 'null' => true],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false], 'description' => ['type' => 'TEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'todo'], 'due_at' => ['type' => 'TIMESTAMP', 'null' => true],
                'completed_at' => ['type' => 'TIMESTAMP', 'null' => true], 'created_at' => ['type' => 'TIMESTAMP', 'null' => false], 'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addKey(['ticket_id', 'status']); $this->forge->addKey(['assigned_worker_id', 'status']);
            $this->forge->addForeignKey('ticket_id', 'production_tickets', 'id', 'CASCADE', 'CASCADE', 'fk_tasks_ticket');
            $this->forge->addForeignKey('assigned_worker_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_tasks_worker');
            $this->forge->createTable('production_tasks');
        }
    }

    public function down()
    {
        foreach (['production_tasks', 'production_tickets', 'notifications', 'payments'] as $table) {
            if ($this->db->tableExists($table)) $this->forge->dropTable($table);
        }
    }
}
