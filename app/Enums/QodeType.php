<?php

namespace App\Enums;

enum QodeType: string
{
    case Content = 'content';
    case LinkHub = 'link_hub';
    case Form = 'form';
    case FileDownload = 'file_download';

    public function label(): string
    {
        return match ($this) {
            self::Content => 'Content',
            self::LinkHub => 'Link hub',
            self::Form => 'Form',
            self::FileDownload => 'File download',
        };
    }
}
