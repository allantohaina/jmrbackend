<?php

namespace App\Database\Migrations;

use App\Database\Migrations\FieldHelpers;
use CodeIgniter\Database\Migration;
use Throwable;

class CreateDocumentsTable extends Migration
{
    use FieldHelpers;

    public function up()
    {
        if ($this->db->tableExists('documents')) {
            return;
        }

        $this->forge->addField([
            'id' => $this->uuidField(),
            'client_id' => $this->uuidFkField(),
            'devis_id' => $this->uuidFkField(true),
            'type' => $this->varcharField(50),
            'nom_original' => $this->varcharField(255),
            'chemin_stocke' => $this->varcharField(500),
            'mime_type' => $this->varcharField(255),
            'taille_bytes' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'uploaded_by' => $this->uuidFkField(),
            'created_at' => $this->timestampField(true),
            'deleted_at' => $this->timestampField(true),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('client_id');
        $this->forge->addKey('devis_id');
        $this->forge->addKey('type');

        // Table créée SANS les FK d'abord : sur MySQL, errno 150 survient
        // quand la nouvelle table hérite d'un charset/collation différent
        // de celui des tables parentes (users, quotes). On aligne puis on
        // pose les FK en ALTER séparé.
        $this->forge->createTable('documents');
        $this->alignCollationWithParents();
        $this->addForeignKeys();
    }

    public function down()
    {
        if ($this->db->tableExists('documents')) {
            foreach (['fk_documents_client', 'fk_documents_devis', 'fk_documents_uploader'] as $key) {
                $this->forge->dropForeignKey('documents', $key, true);
            }
            $this->forge->dropTable('documents');
        }
    }

    private function isMySQL(): bool
    {
        return str_contains(strtolower((string) $this->db->DBDriver), 'mysql');
    }

    /**
     * Aligne le charset/collation de `documents` sur celui de `users.id`,
     * pour que les FK passent même si le défaut actuel de la base a changé
     * depuis la création des tables parentes.
     */
    private function alignCollationWithParents(): void
    {
        if (!$this->isMySQL()) {
            return;
        }

        try {
            $row = $this->db->query(
                "SELECT COLLATION_NAME AS c FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'id'"
            )->getRowArray();
            $collation = is_array($row) ? (string) ($row['c'] ?? $row['C'] ?? '') : '';
            if ($collation === '') {
                return;
            }
            $charset = strstr($collation, '_', true) ?: 'utf8mb4';
            $this->db->query(
                'ALTER TABLE `documents` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collation
            );
        } catch (Throwable $e) {
            log_message('error', 'CreateDocumentsTable: alignement collation impossible: ' . $e->getMessage());
        }
    }

    private function addForeignKeys(): void
    {
        if (!$this->isMySQL()) {
            return;
        }

        try {
            $this->db->query(
                'ALTER TABLE `documents`
                 ADD CONSTRAINT `fk_documents_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                 ADD CONSTRAINT `fk_documents_devis` FOREIGN KEY (`devis_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                 ADD CONSTRAINT `fk_documents_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE'
            );
        } catch (Throwable $e) {
            // Ne pas bloquer la migration : la table reste utilisable et
            // l'intégrité est aussi contrôlée côté applicatif (modèle +
            // contrôleur). Investiguer via INFORMATION_SCHEMA ensuite.
            log_message(
                'error',
                'CreateDocumentsTable: FK non posées, à investiguer (INFORMATION_SCHEMA.COLLATIONS vs users/quotes) : ' . $e->getMessage()
            );
        }
    }
}
