<?php

namespace App\Http\Controllers;

use App\Models\Qode;
use App\Support\QodeUrlBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QodeQrCodeController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const CONTENT_TYPES = [
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
    ];

    public function __invoke(Qode $qode, string $format, QodeUrlBuilder $urls): Response
    {
        Gate::authorize('view', $qode);

        abort_unless(array_key_exists($format, self::CONTENT_TYPES), 404);

        $payload = QrCode::format($format)
            ->size(512)
            ->margin(1)
            ->generate($urls->forQode($qode));

        return response($payload, 200, [
            'Content-Type' => self::CONTENT_TYPES[$format],
            'Content-Disposition' => 'attachment; filename="qode-'.$qode->slug.'.'.$format.'"',
        ]);
    }
}
