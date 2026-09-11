<?php

return [
    'authorized_hls_hosts' => array_filter(array_map('trim', explode(',', env('VIDEO_AUTHORIZED_HLS_HOSTS', '')))),
    'authorized_mp4_hosts' => array_filter(array_map('trim', explode(',', env('VIDEO_AUTHORIZED_MP4_HOSTS', '')))),
];
