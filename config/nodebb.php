<?php

return [
    'url' => env('NODEBB_URL', 'https://nodebb-example.com'),

    'categories' => [
        'post' => [
            'name' => 'Posts'
        ],
        'video' => [
            'name' => 'Videos'
        ]
    ],

    'members' => [

        /*
         * Default Account System 
         */
        'system' => [
            'uid' => env('NODEBB_ID', ''),
            'token' => env('NODEBB_TOKEN', '')
        ],

        'user' => [

            /*
             * Default Password for all users when joining the Gamelancer app.
             */
            'default_password' => env('NODEBB_USER_DEFAULT_PASSWORD', 'bE9SQX9yzq@')
        ]
    ]
];
