<?php

namespace App\Support;

class UserAgentDevice
{
    public static function detect(?string $userAgent): string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return 'unknown';
        }

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'bot') || str_contains($ua, 'spider') || str_contains($ua, 'crawler')) {
            return 'bot';
        }

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        if (
            str_contains($ua, 'mobile')
            || str_contains($ua, 'iphone')
            || str_contains($ua, 'android')
        ) {
            return 'mobile';
        }

        return 'desktop';
    }
}
