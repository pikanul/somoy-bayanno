@props([
    'title' => config('app.name'),
    'description' => 'দৈনিক সময় বায়ান্ন - The Daily Somoy Bayanno',
    'canonical' => null,
    'ogType' => 'website',
    'ogImage' => null,
    'jsonLd' => [],
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description }}">
        <meta name="theme-color" content="#067A3B">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="Somoy Bayanno">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.svg') }}">
        @if (config('analytics.google_search_console_verification'))
            <meta name="google-site-verification" content="{{ config('analytics.google_search_console_verification') }}">
        @endif
        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
        @endif
        <meta property="og:site_name" content="The Daily Somoy Bayanno">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        @if ($canonical)
            <meta property="og:url" content="{{ $canonical }}">
        @endif
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
        @endif
        <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $title }}">
        <meta name="twitter:description" content="{{ $description }}">
        @if ($ogImage)
            <meta name="twitter:image" content="{{ $ogImage }}">
        @endif
        <title>{{ $title }}</title>
        @foreach ($jsonLd as $schema)
            <script type="application/ld+json">
                {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endforeach
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=noto-sans-bengali:400,500,600,700|hind-siliguri:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @if (config('analytics.google_analytics_measurement_id'))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode(config('analytics.google_analytics_measurement_id')) }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', @json(config('analytics.google_analytics_measurement_id')), {'anonymize_ip': true});
            </script>
        @endif
        @if (config('analytics.cloudflare_analytics_token'))
            <script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon='{"token":"{{ config('analytics.cloudflare_analytics_token') }}"}'></script>
        @endif
        {{ $head ?? '' }}
    </head>
    <body class="min-h-screen bg-brand-light text-brand-dark antialiased">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-brand-green">
            মূল কনটেন্টে যান
        </a>

        <div class="min-h-screen">
            <x-public.header />
            <x-public.breaking-ticker />

            <main id="main-content" class="public-container py-6 sm:py-8">
                {{ $slot }}
            </main>

            <x-public.footer />
        </div>
    </body>
</html>
