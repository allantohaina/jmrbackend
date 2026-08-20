<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class StaffFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = $request->user ?? null;
        $role = null;
        if (is_array($user)) {
            $role = $user['role'] ?? null;
        } elseif (is_object($user)) {
            $role = $user->role ?? null;
        }

        if ($role !== 'admin' && $role !== 'worker') {
            return service('response')
                ->setJSON(['error' => 'Accès réservé au personnel (administrateur ou collaborateur).'])
                ->setStatusCode(403);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}