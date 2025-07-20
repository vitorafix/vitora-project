<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Authentication Guard
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication guard for your application.
    | You may change this default to utilize a different guard
    | to handle the authentication of your users.
    |
    */

    'defaults' => [
        'guard' => 'web', // Default guard for web-based authentication (session)
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Each authentication guard defines how your users are authenticated.
    | You may configure guards to handle authentication via sessions,
    | API tokens, or even JWT.
    |
    */

    'guards' => [
        'web' => [ // Default guard for web-based authentication (session)
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [ // Default API guard (can be used for simple tokens)
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ],

        'jwt' => [ // New guard for JWT-based authentication
            'driver' => 'jwt', // This driver will be implemented later with the JWT package
            'provider' => 'users', // This can be adjusted based on how JWT handles multi-user types
        ],

        'admin' => [ // New guard for admin users (session-based)
            'driver' => 'session',
            'provider' => 'admins',
        ],

        'editor' => [ // New guard for editor users (session-based)
            'driver' => 'session',
            'provider' => 'editors',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | User providers define how your users are retrieved from your database.
    | You may configure multiple providers to retrieve users from different
    | sources such as Eloquent or your database.
    |
    */

    'providers' => [
        'users' => [ // Default provider for the User model
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'admins' => [ // New provider for the Admin model
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        'editors' => [ // New provider for the Editor model
            'driver' => 'eloquent',
            'model' => App\Models\Editor::class,
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations based on your
    | needs for various users or user types.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        // You might want to add separate password reset configurations for admins/editors if needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | The number of seconds before the password confirmation times out and
    | the user is prompted to re-enter their password.
    |
    */

    'password_timeout' => 10800, // 3 hours
];

