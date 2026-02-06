<?php

namespace App\Filters;

use App\Application\Security\AdminAuthorizationService;
use App\Application\Shared\Result;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $service = new AdminAuthorizationService();
        $result = $service->authorize($request->user ?? null);

        if ($result->getType() !== Result::TYPE_OK) {
            return service('response')
                ->setJSON($result->getPayload())
                ->setStatusCode($result->getStatus());
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}

