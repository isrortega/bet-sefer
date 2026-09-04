<?php

namespace App\Enums;

enum CopyStatus: string
{
    case Available = 'available';
    case OnLoan = 'on_loan';
    case Reserved = 'reserved';
    case InRepair = 'in_repair';
    case Lost = 'lost';
    case AtReception = 'at_reception';
    case InTransit = 'in_transit';
}
