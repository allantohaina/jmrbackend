<?php

namespace App\History;

use App\Application\History\TokenHistory\LogTokenHistoryInput;
use App\Application\History\TokenHistory\LogTokenHistoryUseCase;
use CodeIgniter\HTTP\IncomingRequest;

class TokenHistory
{
    public function __construct(
        private readonly ?LogTokenHistoryUseCase $useCase = null
    ) {
    }

    public function log(
        IncomingRequest $request,
        string $action,
        ?string $userId,
        ?string $jti,
        ?string $refreshTokenId,
        ?array $meta = null
    ): void {
        $this->useCase()->execute(new LogTokenHistoryInput(
            $action,
            $userId,
            $jti,
            $refreshTokenId,
            $meta,
            $request->getIPAddress(),
            substr((string) $request->getUserAgent(), 0, 255)
        ));
    }

    private function useCase(): LogTokenHistoryUseCase
    {
        return $this->useCase ?? service('logTokenHistoryUseCase');
    }
}
