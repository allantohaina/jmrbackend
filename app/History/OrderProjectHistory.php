<?php

namespace App\History;

use App\Models\OrderProjectHistoryModel;

class OrderProjectHistory
{
    public function log(
        string $action,
        ?string $projectId,
        ?string $status,
        ?array $details,
        ?string $actorUserId
    ): void {
        $model = new OrderProjectHistoryModel();

        $model->insert([
            'id' => $this->uuidV4(),
            'project_id' => $projectId,
            'status' => $status,
            'action' => $action,
            'details' => $details ? json_encode($details) : null,
            'actor_user_id' => $actorUserId,
            'created_at' => date('Y-m-d H:i:s'),
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
