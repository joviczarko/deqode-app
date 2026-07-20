<div style="text-align: center;">
    @if (filled($qrUrl ?? null))
        <img
            src="{{ $qrUrl }}"
            alt="QR code"
            width="160"
            height="160"
            style="display: inline-block; background: #fff; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"
        >
        <div style="margin-top: 12px;" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                @click="open = ! open"
                style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; font-size: 13px; cursor: pointer;"
            >
                Download
                <span aria-hidden="true">▾</span>
            </button>
            <div
                x-show="open"
                x-cloak
                style="margin-top: 6px; display: inline-block; text-align: left; min-width: 120px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,.08);"
            >
                <a href="{{ $qrSvgUrl }}" target="_blank" style="display: block; padding: 8px 12px; font-size: 13px; color: inherit; text-decoration: none;">SVG</a>
                <a href="{{ $qrPngUrl }}" target="_blank" style="display: block; padding: 8px 12px; font-size: 13px; color: inherit; text-decoration: none; border-top: 1px solid #f3f4f6;">PNG</a>
            </div>
        </div>
    @else
        <p style="margin: 0; font-size: 14px; color: #6b7280;">QR available after save.</p>
    @endif
</div>
