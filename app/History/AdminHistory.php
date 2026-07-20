<?php

namespace App\History;

use CodeIgniter\HTTP\RequestInterface;

class AdminHistory extends BaseHistory
{
    public function logUserUpdate(RequestInterface $request, ?string $actorId, string $userId, ?array $before, ?array $after): void
    {
        $this->audit($request, 'admin.user.update', 'users', $userId, $before, $after, $actorId);
    }

    public function logUserDelete(RequestInterface $request, ?string $actorId, string $userId, ?array $before): void
    {
        $this->audit($request, 'admin.user.delete', 'users', $userId, $before, null, $actorId);
    }
}
