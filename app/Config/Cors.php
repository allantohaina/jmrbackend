<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cross-Origin Resource Sharing (CORS) Configuration
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */
class Cors extends BaseConfig
{
    /**
     * The default CORS configuration.
     *
     * @var array{
     *      allowedOrigins: list<string>,
     *      allowedOriginsPatterns: list<string>,
     *      supportsCredentials: bool,
     *      allowedHeaders: list<string>,
     *      exposedHeaders: list<string>,
     *      allowedMethods: list<string>,
     *      maxAge: int,
     *  }
     */
    public array $default = [
        /**
         * Origins for the `Access-Control-Allow-Origin` header.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
         *
         * E.g.:
         *   - ['http://localhost:8080']
         *   - ['https://www.example.com']
         */
        'allowedOrigins' => [
            'https://jmrtextile.com',
            'https://www.jmrtextile.com',
            'https://api.jmrtextile.com',
            'https://admin.jmrtextile.com',
            'https://worker.jmrtextile.com',
            'http://jmrtextile.com',
            'http://www.jmrtextile.com',
            'http://api.jmrtextile.com',
            'http://admin.jmrtextile.com',
            'http://worker.jmrtextile.com',
        ],

        /**
         * Origin regex patterns for the `Access-Control-Allow-Origin` header.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
         *
         * NOTE: A pattern specified here is part of a regular expression. It will
         *       be actually `#\A<pattern>\z#`.
         *
         * E.g.:
         *   - ['https://\w+\.example\.com']
         */
        'allowedOriginsPatterns' => [],

        /**
         * Weather to send the `Access-Control-Allow-Credentials` header.
         *
         * The Access-Control-Allow-Credentials response header tells browsers whether
         * the server allows cross-origin HTTP requests to include credentials.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Credentials
         */
        'supportsCredentials' => true,

        /**
         * Set headers to allow.
         *
         * The Access-Control-Allow-Headers response header is used in response to
         * a preflight request which includes the Access-Control-Request-Headers to
         * indicate which HTTP headers can be used during the actual request.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
         */
        'allowedHeaders' => ['Content-Type', 'Authorization', 'X-Authorization', 'X-Requested-With', 'Accept'],

        /**
         * Set headers to expose.
         *
         * The Access-Control-Expose-Headers response header allows a server to
         * indicate which response headers should be made available to scripts running
         * in the browser, in response to a cross-origin request.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Expose-Headers
         */
        'exposedHeaders' => [],

        /**
         * Set methods to allow.
         *
         * The Access-Control-Allow-Methods response header specifies one or more
         * methods allowed when accessing a resource in response to a preflight
         * request.
         *
         * E.g.:
         *   - ['GET', 'POST', 'PUT', 'DELETE']
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods
         */
        'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

        /**
         * Set how many seconds the results of a preflight request can be cached.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Max-Age
         */
        'maxAge' => 7200,
    ];

    public function __construct()
    {
        parent::__construct();

        $allowedOrigins = $this->parseList((string) getenv('CORS_ALLOWED_ORIGINS'));
        if ($allowedOrigins !== []) {
            $this->default['allowedOrigins'] = $allowedOrigins;
        }

        $allowedOriginPatterns = $this->parseList((string) getenv('CORS_ALLOWED_ORIGIN_PATTERNS'));
        if ($allowedOriginPatterns !== []) {
            $this->default['allowedOriginsPatterns'] = $allowedOriginPatterns;
        }

        $allowedHeaders = $this->parseList((string) getenv('CORS_ALLOWED_HEADERS'));
        if ($allowedHeaders !== []) {
            $this->default['allowedHeaders'] = $allowedHeaders;
        }

        $exposedHeaders = $this->parseList((string) getenv('CORS_EXPOSED_HEADERS'));
        if ($exposedHeaders !== []) {
            $this->default['exposedHeaders'] = $exposedHeaders;
        }

        $allowedMethods = $this->parseList((string) getenv('CORS_ALLOWED_METHODS'));
        if ($allowedMethods !== []) {
            $this->default['allowedMethods'] = $allowedMethods;
        }

        $supportsCredentials = getenv('CORS_SUPPORTS_CREDENTIALS');
        if ($supportsCredentials !== false && $supportsCredentials !== '') {
            $parsed = filter_var($supportsCredentials, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($parsed !== null) {
                $this->default['supportsCredentials'] = $parsed;
            }
        }

        $maxAge = getenv('CORS_MAX_AGE');
        if ($maxAge !== false && $maxAge !== '' && is_numeric($maxAge)) {
            $this->default['maxAge'] = max(0, (int) $maxAge);
        }
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
}
