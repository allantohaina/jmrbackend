<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ConnectionTraceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        log_message(
            'info',
            '[api][backend] -> {method} {path} from {ip}',
            [
                'method' => $request->getMethod(),
                'path' => $this->requestPath($request),
                'ip' => $request->getIPAddress(),
            ],
        );

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        log_message(
            $response->getStatusCode() >= 400 ? 'warning' : 'info',
            '[api][backend] <- {method} {path} status={status}',
            [
                'method' => $request->getMethod(),
                'path' => $this->requestPath($request),
                'status' => $response->getStatusCode(),
            ],
        );

        return null;
    }

    private function requestPath(RequestInterface $request): string
    {
        $path = trim((string) $request->getUri()->getPath(), '/');
        $query = (string) $request->getUri()->getQuery();

        if ($query === '') {
            return '/' . $path;
        }

        return '/' . $path . '?' . $query;
    }
}
