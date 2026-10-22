<?php

namespace App\Domain\Identity;

final readonly class AuthorizationDecision
{
    public function __construct(
        public bool $allowed,
        public string $reason,
    ) {}

    public static function allow(): self
    {
        return new self(true, 'allowed');
    }

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
