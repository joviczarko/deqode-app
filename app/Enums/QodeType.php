<?php

namespace App\Enums;

enum QodeType: string
{
    case Content = 'content';
    case LinkHub = 'link_hub';

    public function label(): string
    {
        return match ($this) {
            self::Content => 'Content',
            self::LinkHub => 'Link hub',
        };
    }
}
