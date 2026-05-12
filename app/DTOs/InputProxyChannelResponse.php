<?php

namespace App\DTOs;

class InputProxyChannelResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly array $channels = [],
        public readonly ?string $error = null,
    ) {}

    public static function success(array $channels): self
    {
        return new self(true, $channels);
    }

    public static function failure(string $error): self
    {
        return new self(false, [], $error);
    }
}
