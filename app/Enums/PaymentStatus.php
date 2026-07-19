<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case Failed = 'failed';
    case Pending = 'pending';
    case Cancelled = 'cancelled';
}
