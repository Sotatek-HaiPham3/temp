<?php

return [
    'aws_region' => env('AWS_DEFAULT_REGION', 'us-west-1'),

    'aws_access_key' => env('AWS_ACCESS_KEY_ID'),

    'aws_secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),

    'app' => [
        'firehose_stream_name' => env('AWS_FIREHOSE_STREAM_NAME', 'test-delivery'),

        'elasticsearch' => [
            'host' => env('ER_HOST', null),
            'port' => env('ER_PORT', ''),
            'scheme' => env('ER_SCHEMA', 'https'),
            'username' => env('ER_USERNAME', null),
            'password' => env('ER_PASSWORD', null)
        ]
    ]
];
