<?php

return [
    /*
    * Application front end url.
    */
    'url' => env('FRONTEND_URL', 'http://localhost:3000'),

    /*
    * Application admin panel url.
    */
    'admin_url' => env('ADMIN_URL', 'http://localhost:3000'),

    /*
    * Contact mail to address.
    */
    'mail_to_address' => env('MAIL_TO_ADDRESS', 'eliyas.r.u@gmail.com'),

    /*
     * User email verification url.
     */
    'email_verify_url' => env('FRONTEND_EMAIL_VERIFY_URL', '/auth/email-verify?queryURL='),

    /*
     * User email verification url.
     */
    'reset_password_url' => env('FRONTEND_RESET_PASSWORD_URL', '/auth/reset-password/'),

    //AWS END POINT
    'aws_url' => env('AWS_URL'),

    'cx_brainstorm' => [
        'name' => env('CX_BRAINSTORM_NAME'),
        'url' => env('CX_BRAINSTORM_URL'),
        'mail_from_address' => env('CX_BRAINSTORM_MAIL_FROM_ADDRESS'),
        'mail_to_address' => env('CX_BRAINSTORM_MAIL_TO_ADDRESS'),
        'facebook' => [
            'client_id' => env('CX_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('CX_FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('CX_FACEBOOK_REDIRECT_URL'),
        ],

        'twitter' => [
            'client_id' => env('CX_TWITTER_CLIENT_ID'),
            'client_secret' => env('CX_TWITTER_CLIENT_SECRET'),
            'redirect' => env('CX_TWITTER_REDIRECT_URL'),
        ],

        'linkedin' => [
            'client_id' => env('CX_LINKEDIN_CLIENT_ID'),
            'client_secret' => env('CX_LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('CX_LINKEDIN_REDIRECT_URL'),
        ],

        'google' => [
            'client_id' => env('CX_GOOGLE_CLIENT_ID'),
            'client_secret' => env('CX_GOOGLE_CLIENT_SECRET'),
            'redirect' => env('CX_GOOGLE_REDIRECT_URL'),
        ],
    ],

    /*
    * get supported artisan commands.
    */
    'artisan_commands' => [
        'route:cache' => [
            'text' => 'Create a route cache file for faster route registration.',
            'class' => 'primary'
        ],
        'config:cache' => [
            'text' => 'Create a cache file for faster configuration loading.',
            'class' => 'primary'
        ],
        'optimize' => [
            'text' => 'Cache the framework bootstrap files.',
            'class' => 'primary'
        ],
        'view:cache' => [
            'text' => 'Compile all of the application\'s Blade templates.',
            'class' => 'primary'
        ],
        'storage:link' => [
            'text' => 'Create the symbolic links configured for the application.',
            'class' => 'primary'
        ],
        'route:clear' => [
            'text' => 'Remove the route cache file.',
            'class' => 'warning'
        ],
        'config:clear' => [
            'text' => 'Remove the configuration cache file.',
            'class' => 'warning'
        ],
        'cache:clear' => [
            'text' => 'Flush the application cache.',
            'class' => 'warning'
        ],
        'view:clear' => [
            'text' => 'Clear all compiled view files.',
            'class' => 'warning'
        ],
        'permission:cache-reset' => [
            'text' => 'Reset the permission cache.',
            'class' => 'warning'
        ],
        'auth:clear-resets' => [
            'text' => 'Flush expired password reset tokens.',
            'class' => 'warning'
        ],
        'media-library:clean' => [
            'text' => 'Clean deprecated conversions and files without related model.',
            'class' => 'warning'
        ],
        'activitylog:clean' => [
            'text' => 'Clean up old records from the activity log.',
            'class' => 'warning'
        ],
        'optimize:clear' => [
            'text' => 'Remove the cached bootstrap files.',
            'class' => 'warning'
        ],
        'clear-compiled' => [
            'text' => 'Remove the compiled class file.',
            'class' => 'warning'
        ]
    ]
];
