<?php

namespace App\Application\History\TokenHistory;

class LogTokenHistoryInput
{
    public function __construct(
        public readonly string $action,
        public readonly ?string $userId,
        public readonly ?string $jti,
        public readonly ?string $refreshTokenId,
        public readonly ?array $meta,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent
    ) {
    }
}
