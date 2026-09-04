<?php

namespace App\Enums;

enum UserStatus: string
{
    case PendingEmail = 'pending_email';
    case PendingIdentity = 'pending_identity';
    case Active = 'active';
    case Suspended = 'suspended';
}
