<?php

namespace App\Domain\Credits;

enum CreditKind: string
{
    case Scan = 'scan';
    case Fix = 'fix';
}
