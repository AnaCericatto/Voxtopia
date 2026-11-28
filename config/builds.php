<?php

return [
    // Version of the build data format. Bump it when the format changes.
    'data_version' => 1,

    'limits' => [
        'width' => 64,
        'height' => 32,
        'depth' => 64,
        'blocks' => 20_000,
        'palette' => 32,
        'steps' => 100,
        'builds_per_user' => 50,
        'title' => 100,
        'step_title' => 100,
        'step_note' => 2_000,
    ],

    // A failed save reports at most this many problems.
    'max_errors' => 10,
];
