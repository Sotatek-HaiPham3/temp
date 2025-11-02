<?php

return [
    'url' => env('MATTEERMOST_URL', 'https://mattermost-example.com'),

    'team' => [
        'name' => env('MATTERMOST_TEAM_NAME', 'gamelancer'),

        /*
         * Specify channel type:
         *  'O' for open - channel public,
         *  'I' for invite only - channel private
         *
         * @link https://api.mattermost.com/#tag/teams/paths/~1teams/post
         */
        'type' => env('MATTERMOST_TEAM_TYPE', 'I')
    ],

    'members' => [

        /*
         * Default Account System 
         */
        'system' => [
            'email' => env('MATTERMOST_SYSTEM_EMAIL', 'admin-example@masttermost.com'),
            'password' => env('MATTERMOST_SYSTEM_PASSWORD', 'password-example')
        ],

        'user' => [

            /*
             * Default Password for all users when joining the Gamelancer app.
             */
            'default_password' => env('MATTERMOST_USER_DEFAULT_PASSWORD', 'bE9SQX9yzq@')
        ]
    ]
];
