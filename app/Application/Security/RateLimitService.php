<?php

namespace App\Application\Security;

use App\Application\Shared\Result;
use CodeIgniter\HTTP\RequestInterface;

class RateLimitService
{
    public function check(RequestInterface $request, $arguments = null): Result
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
        $cacheKey = $this->cacheKey($bucket, $ip);
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
            return Result::fail([
                'error' => lang('RateLimit.too_many'),
                'retry_after' => $retryAfter,
                'limit' => $limit['max'],
                'remaining' => 0,
            ], 429);
        }

        return Result::ok([
            'limit' => $limit['max'],
            'remaining' => $remaining,
            'retry_after' => $retryAfter,
        ]);
    }

    private function cacheKey(string $bucket, string $ip): string
    {
        return 'rl_' . hash('sha256', $bucket . '|' . $ip);
    }
}

