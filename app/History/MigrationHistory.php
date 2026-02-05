<?php

namespace App\History;

use App\Models\MigrationHistoryModel;

class MigrationHistory
{
    public function log(string $migration, string $direction, ?int $batch = null, ?array $meta = null): void
    {
        $model = new MigrationHistoryModel();

        $model->insert([
            'id' => $this->uuidV4(),
            'migration' => $migration,
            'direction' => $direction,
            'batch' => $batch,
            'meta' => $meta ? json_encode($meta) : null,
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}
