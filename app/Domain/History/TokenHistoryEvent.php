<?php

namespace App\Domain\History;

use App\Traits\UuidTrait;

class TokenHistoryEvent
{
    use UuidTrait;

    public function __construct(
        public readonly string $id,
        public readonly ?string $userId,
        public readonly string $action,
        public readonly ?string $jti,
        public readonly ?string $refreshTokenId,
        public readonly ?array $meta,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly string $createdAt
    ) {
    }

    public static function create(
        string $action,
        ?string $userId,
        ?string $jti,
        ?string $refreshTokenId,
        ?array $meta,
        ?string $ipAddress,
        ?string $userAgent
    ): self {
        return new self(
            self::uuidV4(),
            $userId,
            $action,
            $jti,
            $refreshTokenId,
            $meta,
            $ipAddress,
            $userAgent,
            date('Y-m-d H:i:s')
        );
    }
}
