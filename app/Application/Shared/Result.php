<?php

namespace App\Application\Shared;

final class Result
{
    public const TYPE_OK = 'ok';
    public const TYPE_CREATED = 'created';
    public const TYPE_FAIL = 'fail';
    public const TYPE_NOT_FOUND = 'not_found';
    public const TYPE_UNAUTHORIZED = 'unauthorized';
    public const TYPE_FORBIDDEN = 'forbidden';

    private string $type;
    private mixed $payload;
    private int $status;

    private function __construct(string $type, mixed $payload, int $status)
    {
        $this->type = $type;
        $this->payload = $payload;
        $this->status = $status;
    }

    public static function ok(mixed $payload = null, int $status = 200): self
    {
        return new self(self::TYPE_OK, $payload, $status);
    }

    public static function created(mixed $payload): self
    {
        return new self(self::TYPE_CREATED, $payload, 201);
    }

    public static function fail(mixed $payload, int $status = 400): self
    {
        return new self(self::TYPE_FAIL, $payload, $status);
    }

    public static function notFound(string $message = 'Not found'): self
    {
        return new self(self::TYPE_NOT_FOUND, $message, 404);
    }

    public static function unauthorized(mixed $payload = 'Unauthorized'): self
    {
        return new self(self::TYPE_UNAUTHORIZED, $payload, 401);
    }

    public static function forbidden(mixed $payload = 'Forbidden'): self
    {
        return new self(self::TYPE_FORBIDDEN, $payload, 403);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isSuccess(): bool
    {
        return in_array($this->type, [self::TYPE_OK, self::TYPE_CREATED]);
    }
}

