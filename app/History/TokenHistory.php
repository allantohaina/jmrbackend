<?php

namespace App\History;

use App\Models\TokenHistoryModel;
use CodeIgniter\HTTP\IncomingRequest;

class TokenHistory
{
    public function log(
        IncomingRequest $request,
        string $action,
        ?string $userId,
        ?string $jti,
        ?string $refreshTokenId,
        ?array $meta = null
    ): void {
        $model = new TokenHistoryModel();

        $model->insert([
            'id' => $this->uuidV4(),
            'user_id' => $userId,
            'action' => $action,
            'jti' => $jti,
            'refresh_token_id' => $refreshTokenId,
            'meta' => $meta ? json_encode($meta) : null,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => substr((string) $request->getUserAgent(), 0, 255),
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
