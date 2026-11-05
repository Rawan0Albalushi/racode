<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07090f">
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('racode-appearance');
                var mode = stored === 'light' || stored === 'dark'
                    ? stored
                    : (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
                document.documentElement.setAttribute('data-appearance', mode);
                var meta = document.querySelector('meta[name="theme-color"]');
                if (meta) meta.setAttribute('content', mode === 'light' ? '#f3efe6' : '#07090f');
            } catch (e) {}
        })();
    </script>
    @php
        $seo = $page['props']['seo'] ?? [];
        $site = $page['props']['site'] ?? [];
        $colors = $site['colors'] ?? [];
        $hex = fn ($value, $fallback) => is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : $fallback;
    @endphp
    <title>{{ $seo['title'] ?? 'RACODE' }}</title>
    <meta name="description" content="{{ $seo['description'] ?? '' }}">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
    @foreach (($seo['alternates'] ?? []) as $lang => $href)
        <link rel="alternate" hreflang="{{ $lang === 'x-default' ? 'x-default' : $lang }}" href="{{ $href }}">
    @endforeach
    <meta property="og:title" content="{{ $seo['title'] ?? 'RACODE' }}">
    <meta property="og:description" content="{{ $seo['description'] ?? '' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
    <meta property="og:image" content="{{ $seo['image'] ?? url('/brand/og.png') }}">
    <meta property="og:locale" content="{{ $seo['locale'] ?? 'ar_OM' }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ $site['favicon_url'] ?? '/favicon.svg' }}" type="image/svg+xml">
    <style>
        :root {
            --color-midnight: {{ $hex($colors['midnight'] ?? null, '#1C2B33') }};
            --color-burgundy: {{ $hex($colors['burgundy'] ?? null, '#163E38') }};
            --color-rose: {{ $hex($colors['rose'] ?? null, '#9F2D1C') }};
            --color-blush: {{ $hex($colors['blush'] ?? null, '#D5E6DE') }};
            --color-ivory: {{ $hex($colors['ivory'] ?? null, '#E7F0EA') }};
        }
    </style>
    @if (! empty($site['analytics']['ga4']))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $site['analytics']['ga4'] }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($site['analytics']['ga4']));
        </script>
    @endif
    @if (! empty($site['analytics']['plausible']))
        <script defer data-domain="{{ $site['analytics']['plausible'] }}" src="https://plausible.io/js/script.js"></script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="min-h-screen overflow-x-clip antialiased">
    @inertia
</body>
</html>
