<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if user is authenticated and is admin
        if (!isset($request->user) || (($request->user['role'] ?? $request->user->role ?? null) !== 'admin')) {
            return service('response')
                ->setJSON(['error' => 'Accès refusé. Droits administrateur requis.'])
                ->setStatusCode(403);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
