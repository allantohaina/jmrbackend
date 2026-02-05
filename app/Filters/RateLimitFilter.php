<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $bucket = is_array($arguments) && isset($arguments[0]) ? (string) $arguments[0] : 'default';

        $limits = [
            'login' => [
                'max' => (int) (getenv('RATE_LIMIT_LOGIN_MAX') ?: 10),
                'window' => (int) (getenv('RATE_LIMIT_LOGIN_WINDOW') ?: 60),
            ],
            'auth' => [
                'max' => (int) (getenv('RATE_LIMIT_AUTH_MAX') ?: 30),
                'window' => (int) (getenv('RATE_LIMIT_AUTH_WINDOW') ?: 60),
            ],
            'default' => [
                'max' => (int) (getenv('RATE_LIMIT_DEFAULT_MAX') ?: 60),
                'window' => (int) (getenv('RATE_LIMIT_DEFAULT_WINDOW') ?: 60),
            ],
        ];

        $limit = $limits[$bucket] ?? $limits['default'];

        $ip = $request->getIPAddress();
        $cacheKey = 'rl:' . $bucket . ':' . $ip;
        $now = time();

        $entry = cache()->get($cacheKey);
        if (!is_array($entry)) {
            $entry = [
                'count' => 0,
                'reset' => $now + $limit['window'],
            ];
        }

        if ($now > (int) $entry['reset']) {
            $entry = [
                'count' => 0,
                'reset' => $now + $limit['window'],
            ];
        }

        $entry['count']++;

        $remaining = max(0, $limit['max'] - $entry['count']);
        $retryAfter = max(0, (int) $entry['reset'] - $now);

        cache()->save($cacheKey, $entry, $limit['window']);

        if ($entry['count'] > $limit['max']) {
            $response = service('response');
            return $response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setHeader('X-RateLimit-Limit', (string) $limit['max'])
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setJSON([
                    'error' => 'Trop de requêtes, réessayez plus tard.',
                    'retry_after' => $retryAfter,
                ]);
        }

        service('response')
            ->setHeader('X-RateLimit-Limit', (string) $limit['max'])
            ->setHeader('X-RateLimit-Remaining', (string) $remaining);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
