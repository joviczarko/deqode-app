<?php

namespace App\Support;

use App\Enums\DomainType;
use App\Models\Domain;
use App\Models\Qode;

class QodeUrlBuilder
{
    public function forQode(Qode $qode): string
    {
        $domain = $qode->domain()->first() ?? $qode->domain;

        return $this->forDomainAndSlug($domain, (string) $qode->slug);
    }

    public function forDomainAndSlug(Domain $domain, string $slug): string
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
        $host = $domain->hostname;
        $prefix = trim((string) config('deqode.scan_path_prefix', ''), '/');

        $usePathPrefix = $prefix !== ''
            && (
                $host === (string) config('deqode.scan_host')
                || $host === (string) config('deqode.platform_domain')
                || $domain->type === DomainType::Platform
            );

        if ($usePathPrefix) {
            return "{$scheme}://{$host}/{$prefix}/{$slug}";
        }

        return "{$scheme}://{$host}/{$slug}";
    }

    public function localResolvePath(string $slug): string
    {
        $prefix = trim((string) config('deqode.scan_path_prefix', 'r'), '/');

        return $prefix === '' ? '/'.$slug : '/'.$prefix.'/'.$slug;
    }
}
