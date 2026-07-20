<div style="text-align: center;">
    @if (filled($qrUrl ?? null))
        <img
            src="{{ $qrUrl }}"
            alt="QR code"
            width="160"
            height="160"
            style="display: inline-block; background: #fff; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"
        >
    @else
        <p style="margin: 0; font-size: 14px; color: #6b7280;">QR available after save.</p>
    @endif
</div>
