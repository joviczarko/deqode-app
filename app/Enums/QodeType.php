<?php

namespace App\Enums;

enum QodeType: string
{
    case Redirect = 'redirect';
    case Content = 'content';

    public function label(): string
    {
        return match ($this) {
            self::Redirect => 'Redirect',
            self::Content => 'Content',
        };
    }
}
