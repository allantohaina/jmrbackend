<?php

namespace App\Application\Security;

use App\Application\Shared\Result;

class AdminAuthorizationService
{
    public function authorize(mixed $user): Result
    {
        $role = null;
        if (is_array($user)) {
            $role = $user['role'] ?? null;
        } elseif (is_object($user)) {
            $role = $user->role ?? null;
        }

        if ($role !== 'admin') {
            return Result::forbidden(['error' => 'AccÃ¨s refusÃ©. Droits administrateur requis.']);
        }

        return Result::ok(true);
    }
}

