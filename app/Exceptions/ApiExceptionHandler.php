<?php

namespace App\Exceptions;

use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions as ExceptionsConfig;
use Throwable;

class ApiExceptionHandler implements ExceptionHandlerInterface
{
    private ExceptionHandler $defaultHandler;

    public function __construct(ExceptionsConfig $config)
    {
        $this->defaultHandler = new ExceptionHandler($config);
    }

    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        if ($request instanceof IncomingRequest && $this->shouldReturnJson($request)) {
            try {
                $response->setStatusCode($statusCode);
            } catch (Throwable) {
                $statusCode = 500;
                $response->setStatusCode($statusCode);
            }

            $payload = $this->buildPayload($exception, $statusCode);

            $response->setJSON($payload)->send();

            if (ENVIRONMENT !== 'testing') {
                exit($exitCode);
            }

            return;
        }

        $this->defaultHandler->handle($exception, $request, $response, $statusCode, $exitCode);
    }

    private function shouldReturnJson(IncomingRequest $request): bool
    {
        $accept = strtolower($request->getHeaderLine('accept'));
        $path = ltrim($request->getPath(), '/');

        return str_starts_with($path, 'api')
            || str_contains($accept, 'application/json')
            || $request->isAJAX();
    }

    private function buildPayload(Throwable $exception, int $statusCode): array
    {
        $message = lang('Common.errors.unexpected');
        $context = null;

        if ($exception instanceof ApiException) {
            $message = $exception->getMessage();
            $context = $exception->getContext();
        }

        $payload = [
            'error' => $message,
            'status' => $statusCode,
        ];

        if ($context !== null) {
            $payload['context'] = $context;
        }

        if (ENVIRONMENT !== 'production') {
            $payload['debug'] = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return $payload;
    }
}
