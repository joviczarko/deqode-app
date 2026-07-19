<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Paid = 'paid';
    case Failed = 'failed';
    case Open = 'open';
    case Cancelled = 'cancelled';
}
