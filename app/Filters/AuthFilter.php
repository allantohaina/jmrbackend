<?php

namespace App\Filters;

use App\Application\Security\AuthContextService;
use App\Application\Shared\Result;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $service = new AuthContextService();
        $result = $service->authenticate($request);

        if ($result->getType() !== Result::TYPE_OK) {
            return service('response')
                ->setJSON($result->getPayload())
                ->setStatusCode($result->getStatus());
        }

        $request->user = $result->getPayload();

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}

