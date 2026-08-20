<?php

namespace App\Database\Migrations;

trait FieldHelpers
{
    protected function uuidField(bool $nullable = false): array
    {
        return [
            'type' => 'VARCHAR',
            'constraint' => '36',
            'null' => $nullable,
        ];
    }

    protected function uuidFkField(bool $nullable = false): array
    {
        $isPg = str_contains(strtolower((string) $this->db->DBDriver), 'postgre');
        $field = [
            'type' => $isPg ? 'UUID' : 'VARCHAR',
            'null' => $nullable,
        ];

        if (! $isPg) {
            $field['constraint'] = '36';
        }

        return $field;
    }

    protected function varcharField(int $length, bool $nullable = false, ?string $default = null): array
    {
        $field = [
            'type' => 'VARCHAR',
            'constraint' => $length,
            'null' => $nullable,
        ];

        if ($default !== null) {
            $field['default'] = $default;
        }

        return $field;
    }

    protected function timestampField(bool $nullable = false): array
    {
        return [
            'type' => 'TIMESTAMP',
            'null' => $nullable,
        ];
    }

    protected function textField(bool $nullable = true): array
    {
        return [
            'type' => 'TEXT',
            'null' => $nullable,
        ];
    }

    protected function jsonField(bool $nullable = true): array
    {
        return [
            'type' => 'TEXT',
            'null' => $nullable,
        ];
    }

    protected function booleanField(bool $default = false): array
    {
        return [
            'type' => 'BOOLEAN',
            'default' => $default,
        ];
    }

    protected function ipAddressField(): array
    {
        return $this->varcharField(64, true);
    }

    protected function userAgentField(): array
    {
        return $this->varcharField(255, true);
    }
}
