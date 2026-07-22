<?php

namespace App\Support;

class DnsTxtLookup
{
    /**
     * @return list<string>
     */
    public function texts(string $hostname): array
    {
        $records = @dns_get_record($hostname, DNS_TXT);

        if (! is_array($records)) {
            return [];
        }

        $texts = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $txt = $record['txt'] ?? null;

            if (is_string($txt) && $txt !== '') {
                $texts[] = $txt;
            }
        }

        return $texts;
    }
}
