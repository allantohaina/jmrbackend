<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    private const BUCKETS = [
        'login'   => ['capacity' => 10, 'seconds' => 60],
        'auth'    => ['capacity' => 30, 'seconds' => 60],
        'default' => ['capacity' => 60, 'seconds' => 60],
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $bucket = is_array($arguments) && isset($arguments[0]) ? (string) $arguments[0] : 'default';
        $limits = self::BUCKETS[$bucket] ?? self::BUCKETS['default'];

        $throttler = service('throttler');
        $ip = $request->getIPAddress();
        $key = hash('sha256', $bucket . '|' . $ip);

        if ($throttler->check($key, $limits['capacity'], $limits['seconds']) === false) {
            $tokenTime = $throttler->getTokenTime();
            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) ($tokenTime ?: 0))
                ->setHeader('X-RateLimit-Limit', (string) $limits['capacity'])
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setJSON([
                    'error' => 'Trop de requêtes. Réessayez plus tard.',
                    'retry_after' => $tokenTime ?: 0,
                ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}

