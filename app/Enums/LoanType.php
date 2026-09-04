<?php

namespace App\Enums;

enum LoanType: string
{
    case General = 'general';
    case Reference = 'reference';
    case Periodical = 'periodical';
}
