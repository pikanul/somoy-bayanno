<?php

return [
    'disk' => env('MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),
    'max_bytes' => (int) env('MEDIA_MAX_BYTES', 8 * 1024 * 1024),
    'download_timeout' => (int) env('MEDIA_DOWNLOAD_TIMEOUT', 8),
    'redirect_limit' => (int) env('MEDIA_REDIRECT_LIMIT', 3),
    'thumbnail_width' => (int) env('MEDIA_THUMBNAIL_WIDTH', 480),
];
