<?php

namespace App\Enums;

enum CheckoutResult: string
{
    case Success = 'success';
    case Fail = 'fail';
    case Cancel = 'cancel';
}
