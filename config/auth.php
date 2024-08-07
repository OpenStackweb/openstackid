<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */
    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session", "token"
    |
    */

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'custom',
            'model'  => auth\User::class,
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
    | Here you may set the options for resetting passwords including the view
    | that is your password reset e-mail. You may also set the name of the
    | table that maintains all of the reset tokens for your application.
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expire time is the number of minutes that the reset token should be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'custom',
            'email' => 'auth.emails.password',
            'table' => 'password_resets',
            'expire' => 60,
        ],
    ],

    // in seconds
    'password_reset_lifetime' => env('AUTH_PASSWORD_RESET_LIFETIME', 1800),
    'password_min_length' => env('AUTH_PASSWORD_MIN_LENGTH', 8),
    'password_max_length' => env('AUTH_PASSWORD_MAX_LENGTH', 30),
    'password_shape_pattern' => env('AUTH_PASSWORD_SHAPE_PATTERN', '^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-])'),
    'password_shape_warning' => env('AUTH_PASSWORD_SHAPE_WARNING', 'Password must include at least one uppercase letter, one lowercase letter, one number, and one special character.'),
    'verification_email_lifetime' => env("AUTH_VERIFICATION_EMAIL_LIFETIME", 600),
    'allows_native_auth' => env('AUTH_ALLOWS_NATIVE_AUTH', 1),
    'allows_native_on_config' => env('AUTH_ALLOWS_NATIVE_AUTH_CONFIG', 1),
    'allows_opt_auth' => env('AUTH_ALLOWS_OTP_AUTH', 1),
];
