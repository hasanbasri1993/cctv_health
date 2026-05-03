<?php

namespace App\DTOs;

class DeviceHealthResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status = 'unknown',
        public readonly ?int $responseTimeMs = null,
        public readonly ?string $firmwareVersion = null,
        public readonly ?string $model = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(
        string $status,
        int $responseTimeMs,
        ?string $firmwareVersion = null,
        ?string $model = null,
    ): self {
        return new self(true, $status, $responseTimeMs, $firmwareVersion, $model);
    }

    public static function failure(string $error): self
    {
        return new self(false, 'offline', null, null, null, $error);
    }
}
