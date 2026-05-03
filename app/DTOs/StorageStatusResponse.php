<?php

namespace App\DTOs;

class StorageStatusResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly array $storages = [],
        public readonly ?string $error = null,
    ) {}

    public static function success(array $storages): self
    {
        return new self(true, $storages);
    }

    public static function failure(string $error): self
    {
        return new self(false, [], $error);
    }
}
