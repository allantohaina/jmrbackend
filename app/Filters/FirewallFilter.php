<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class FirewallFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $this->envBool('FIREWALL_ENABLED', true)) {
            return null;
        }

        $ipAddress = (string) $request->getIPAddress();

        $blockedRules = $this->parseList((string) getenv('FIREWALL_BLOCKED_IPS'));
        if ($blockedRules !== [] && $this->ipMatchesAnyRule($ipAddress, $blockedRules)) {
            return $this->forbidden('IP blocked by firewall.');
        }

        $allowedRules = $this->parseList((string) getenv('FIREWALL_ALLOWED_IPS'));
        if ($allowedRules !== [] && ! $this->ipMatchesAnyRule($ipAddress, $allowedRules)) {
            return $this->forbidden('IP not allowed by firewall.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function forbidden(string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode(403)
            ->setJSON([
                'status'  => 'error',
                'message' => $message,
            ]);
    }

    private function envBool(string $name, bool $default): bool
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }

    /**
     * @return list<string>
     */
    private function parseList(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $items = array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $item): bool => $item !== '',
        );

        return array_values($items);
    }

    /**
     * @param list<string> $rules
     */
    private function ipMatchesAnyRule(string $ipAddress, array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($this->ipMatchesRule($ipAddress, $rule)) {
                return true;
            }
        }

        return false;
    }

    private function ipMatchesRule(string $ipAddress, string $rule): bool
    {
        if ($rule === '*') {
            return true;
        }

        if (str_contains($rule, '/')) {
            return $this->ipInCidr($ipAddress, $rule);
        }

        if (str_contains($rule, '*')) {
            $pattern = '/\A' . str_replace('\*', '.*', preg_quote($rule, '/')) . '\z/u';

            return preg_match($pattern, $ipAddress) === 1;
        }

        return $ipAddress === $rule;
    }

    private function ipInCidr(string $ipAddress, string $cidr): bool
    {
        [$network, $prefixLength] = array_pad(explode('/', $cidr, 2), 2, null);
        if ($network === '' || $prefixLength === null || ! is_numeric($prefixLength)) {
            return false;
        }

        $ipBinary = inet_pton($ipAddress);
        $networkBinary = inet_pton($network);

        if ($ipBinary === false || $networkBinary === false || strlen($ipBinary) !== strlen($networkBinary)) {
            return false;
        }

        $maxPrefixLength = strlen($ipBinary) * 8;
        $prefix = (int) $prefixLength;

        if ($prefix < 0 || $prefix > $maxPrefixLength) {
            return false;
        }

        $bytes = intdiv($prefix, 8);
        $bits = $prefix % 8;

        if ($bytes > 0 && substr($ipBinary, 0, $bytes) !== substr($networkBinary, 0, $bytes)) {
            return false;
        }

        if ($bits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $bits)) & 0xFF;

        return (ord($ipBinary[$bytes]) & $mask) === (ord($networkBinary[$bytes]) & $mask);
    }
}
