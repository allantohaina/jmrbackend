<?php

namespace App\Filters;

use App\Application\Security\AuthContextService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class OptionalAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $service = new AuthContextService();
        $result = $service->authenticate($request);

        if ($result->isSuccess()) {
            $request->user = $result->getPayload();
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
