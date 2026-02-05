<?php

namespace App\History;

use CodeIgniter\HTTP\IncomingRequest;

class UserHistory extends BaseHistory
{
    public function logRegister(IncomingRequest $request, array $user): void
    {
        $this->audit($request, 'user.register', 'users', $user['id'] ?? null, null, $user, $user['id'] ?? null);
    }

    public function logLogin(IncomingRequest $request, array $user): void
    {
        $this->audit($request, 'user.login', 'users', $user['id'] ?? null, null, [
            'email' => $user['email'] ?? null,
            'role' => $user['role'] ?? null,
        ], $user['id'] ?? null);
    }

    public function logLoginFailed(IncomingRequest $request, ?string $email, ?string $userId, string $reason): void
    {
        $this->audit($request, 'user.login.failed', 'users', $userId, null, [
            'email' => $email,
            'reason' => $reason,
        ], $userId);
    }

    public function logProfileUpdate(IncomingRequest $request, string $userId, ?array $before, ?array $after): void
    {
        $this->audit($request, 'user.profile.update', 'users', $userId, $before, $after, $userId);
    }

    public function logProfileDelete(IncomingRequest $request, string $userId, ?array $before): void
    {
        $this->audit($request, 'user.profile.delete', 'users', $userId, $before, null, $userId);
    }
}
