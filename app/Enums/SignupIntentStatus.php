<?php

namespace App\Enums;

enum SignupIntentStatus: string
{
    case Pending = 'pending';
    case EmailVerified = 'email_verified';
    case Completed = 'completed';
    case Expired = 'expired';
}
