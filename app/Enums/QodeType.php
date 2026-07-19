<?php

namespace App\Enums;

enum QodeType: string
{
    case Content = 'content';

    public function label(): string
    {
        return match ($this) {
            self::Content => 'Content',
        };
    }
}
