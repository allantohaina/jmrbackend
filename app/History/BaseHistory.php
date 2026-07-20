<?php

namespace App\History;

use App\Libraries\AuditLogger;
use CodeIgniter\HTTP\RequestInterface;

class BaseHistory
{
    protected function audit(
        RequestInterface $request,
        string $action,
        string $entityType,
        ?string $entityId,
        ?array $before,
        ?array $after,
        ?string $actorUserId
    ): void {
        // Handle CLIRequest which may not have all methods
        $ipAddress = method_exists($request, 'getIPAddress') ? $request->getIPAddress() : '127.0.0.1';
        $userAgent = method_exists($request, 'getUserAgent') ? substr((string) $request->getUserAgent(), 0, 255) : 'CLI';
        
        AuditLogger::log([
            'id' => $this->uuidV4(),
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_data' => $before ? json_encode($before) : null,
            'after_data' => $after ? json_encode($after) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}
