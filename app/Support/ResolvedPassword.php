<?php

namespace App\Support;

readonly class ResolvedPassword
{
    public function __construct(
        public string $plain,
        public bool $isCustom,
    ) {}

    public function includeInEmail(): bool
    {
        return ! $this->isCustom;
    }
}
