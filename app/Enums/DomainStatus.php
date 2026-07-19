<?php

namespace App\Enums;

enum DomainStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Verified = 'verified';
    case Disabled = 'disabled';
}
