<?php

namespace App\Domain\Production\Assemblage;

use App\Traits\UuidTrait;

class Assemblage
{
    use UuidTrait;

    public function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public string $name,
        public string $status,
        public ?string $details,
        public readonly string $createdAt,
        public string $updatedAt
    ) {
    }

    public static function create(string $projectId, string $name, ?string $details = null): self
    {
        $now = date('Y-m-d H:i:s');
        return new self(
            self::uuidV4(),
            $projectId,
            $name,
            'pending',
            $details,
            $now,
            $now
        );
    }
}
