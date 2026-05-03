<?php

namespace App\DTOs;

class ConnectionTestResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $responseTimeMs = null,
        public readonly ?string $deviceInfo = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(int $responseTimeMs, ?string $deviceInfo = null): self
    {
        return new self(true, $responseTimeMs, $deviceInfo);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, null, $error);
    }
}
