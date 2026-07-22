<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-deqode-wrapper="1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @stack('head')

    @php
        $analytics = is_array(($qode ?? null)?->tenant?->analytics_settings ?? null)
            ? $qode->tenant->analytics_settings
            : [];
        $ga4 = $analytics['ga4_measurement_id'] ?? null;
        $metaPixel = $analytics['meta_pixel_id'] ?? null;
    @endphp

    @if (filled($ga4))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}" data-deqode-ga4="{{ $ga4 }}"></script>
        <script data-deqode-ga4-config="{{ $ga4 }}">
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($ga4));
        </script>
    @endif

    @if (filled($metaPixel))
        <script data-deqode-meta-pixel="{{ $metaPixel }}">
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', @json($metaPixel));
            fbq('track', 'PageView');
        </script>
    @endif

    @stack('analytics-head')
</head>
<body>
    @stack('analytics-body-start')

    @yield('body')

    @if (filled($metaPixel))
        <noscript data-deqode-meta-pixel-noscript="{{ $metaPixel }}">
            <img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id={{ urlencode($metaPixel) }}&ev=PageView&noscript=1"
                 alt="" />
        </noscript>
    @endif

    @stack('analytics-body-end')
</body>
</html>
