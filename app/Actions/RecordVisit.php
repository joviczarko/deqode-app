<?php

namespace App\Actions;

use App\Models\Qode;
use Illuminate\Http\Request;

class RecordVisit
{
    /**
     * Chunk 3a will persist and queue visits. Keep the resolve hook in place.
     */
    public function handle(Qode $qode, Request $request): void
    {
        //
    }
}
