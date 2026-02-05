<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    private int $statusCode;
    private array $context;

    public function __construct(string $message, int $statusCode = 500, array $context = [])
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
