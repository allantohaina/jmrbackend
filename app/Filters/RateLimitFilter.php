<?php

namespace App\Filters;

use App\Application\Security\RateLimitService;
use App\Application\Shared\Result;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $service = new RateLimitService();
        $result = $service->check($request, $arguments);

        $payload = $result->getPayload();

        if ($result->getType() === Result::TYPE_FAIL && $result->getStatus() === 429) {
            $response = service('response');
            return $response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) ($payload['retry_after'] ?? 0))
                ->setHeader('X-RateLimit-Limit', (string) ($payload['limit'] ?? 0))
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setJSON([
                    'error' => $payload['error'] ?? 'Trop de requÃªtes, rÃ©essayez plus tard.',
                    'retry_after' => $payload['retry_after'] ?? 0,
                ]);
        }

        service('response')
            ->setHeader('X-RateLimit-Limit', (string) ($payload['limit'] ?? 0))
            ->setHeader('X-RateLimit-Remaining', (string) ($payload['remaining'] ?? 0));

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}

