<?php

namespace App\Enums;

enum QodeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Draft = 'draft';
}
