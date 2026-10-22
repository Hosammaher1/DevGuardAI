<?php

namespace App\Domain\Identity;

use App\Models\MembershipInvitation;

final readonly class IssuedInvitation
{
    public function __construct(
        public MembershipInvitation $invitation,
        public string $rawToken,
    ) {}
}
