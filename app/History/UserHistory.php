<?php

namespace App\History;

use CodeIgniter\HTTP\RequestInterface;

class UserHistory extends BaseHistory
{
    public function logRegister(RequestInterface $request, array $user): void
    {
        $this->audit($request, 'user.register', 'users', $user['id'] ?? null, null, $user, $user['id'] ?? null);
    }

    public function logLogin(RequestInterface $request, array $user): void
    {
        $this->audit($request, 'user.login', 'users', $user['id'] ?? null, null, [
            'email' => $user['email'] ?? null,
            'role' => $user['role'] ?? null,
        ], $user['id'] ?? null);
    }

    public function logLoginFailed(RequestInterface $request, ?string $email, ?string $userId, string $reason): void
    {
        $this->audit($request, 'user.login.failed', 'users', $userId, null, [
            'email' => $email,
            'reason' => $reason,
        ], $userId);
    }

    public function logProfileUpdate(RequestInterface $request, string $userId, ?array $before, ?array $after): void
    {
        $this->audit($request, 'user.profile.update', 'users', $userId, $before, $after, $userId);
    }

    public function logProfileDelete(RequestInterface $request, string $userId, ?array $before): void
    {
        $this->audit($request, 'user.profile.delete', 'users', $userId, $before, null, $userId);
    }
}
