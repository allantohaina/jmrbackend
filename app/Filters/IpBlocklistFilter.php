<?php

namespace App\Filters;

use App\Models\IpBlocklistModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class IpBlocklistFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $ip = $request->getIPAddress();

        if ((new IpBlocklistModel())->isBlocked($ip)) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'error' => 'Trop de tentatives. Réessayez dans quelques minutes.',
                ]);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
