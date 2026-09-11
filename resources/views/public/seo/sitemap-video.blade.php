{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @foreach ($url['videos'] as $video)
            <video:video>
                <video:thumbnail_loc>{{ $video['thumbnail_loc'] }}</video:thumbnail_loc>
                <video:title>{{ $video['title'] }}</video:title>
                <video:description>{{ $video['description'] }}</video:description>
            </video:video>
        @endforeach
    </url>
@endforeach
</urlset>
