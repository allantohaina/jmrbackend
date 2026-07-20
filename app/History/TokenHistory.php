<?php

namespace App\History;

use App\Application\History\TokenHistory\LogTokenHistoryInput;
use App\Application\History\TokenHistory\LogTokenHistoryUseCase;
use CodeIgniter\HTTP\RequestInterface;

class TokenHistory
{
    public function __construct(
        private readonly ?LogTokenHistoryUseCase $useCase = null
    ) {
    }

    public function log(
        RequestInterface $request,
        string $action,
        ?string $userId,
        ?string $jti,
        ?string $refreshTokenId,
        ?array $meta = null
    ): void {
        // Handle CLIRequest which may not have all methods
        $ipAddress = method_exists($request, 'getIPAddress') ? $request->getIPAddress() : '127.0.0.1';
        $userAgent = method_exists($request, 'getUserAgent') ? substr((string) $request->getUserAgent(), 0, 255) : 'CLI';

        $this->useCase()->execute(new LogTokenHistoryInput(
            $action,
            $userId,
            $jti,
            $refreshTokenId,
            $meta,
            $ipAddress,
            $userAgent
        ));
    }

    private function useCase(): LogTokenHistoryUseCase
    {
        return $this->useCase ?? service('logTokenHistoryUseCase');
    }
}
