<?php

namespace App\Domain\Credits;

use RuntimeException;

final class CreditReservationDenied extends RuntimeException
{
    public function __construct(public readonly string $reason = 'credit_reservation_denied')
    {
        parent::__construct($reason);
    }
}
