<?php

namespace App\Support;

use Sqids\Sqids;

class SqidsEncoder
{
    private Sqids $sqids;

    public function __construct(?string $alphabet = null, ?int $minLength = null)
    {
        $this->sqids = new Sqids(
            alphabet: $alphabet ?? (string) config('deqode.sqids.alphabet'),
            minLength: $minLength ?? (int) config('deqode.sqids.min_length', 8),
        );
    }

    public function encode(int $id): string
    {
        return $this->sqids->encode([$id]);
    }

    /**
     * @return list<int>
     */
    public function decode(string $slug): array
    {
        return $this->sqids->decode($slug);
    }
}
