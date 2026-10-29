<?php

return [
    'embed_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('VIDEO_EMBED_HOSTS', '')),
    ))),
];
