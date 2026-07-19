<?php

namespace App\Http\Controllers;

use App\Models\Qode;
use App\Support\QodeUrlBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QodeQrCodeController extends Controller
{
    public function __invoke(Qode $qode, QodeUrlBuilder $urls): Response
    {
        Gate::authorize('view', $qode);

        $svg = QrCode::format('svg')
            ->size(512)
            ->margin(1)
            ->generate($urls->forQode($qode));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qode-'.$qode->slug.'.svg"',
        ]);
    }
}
