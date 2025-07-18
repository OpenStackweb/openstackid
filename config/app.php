<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => env('APP_LOCALE','en'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        \App\Http\Utils\UtilsProvider::class,
        Repositories\RepositoriesProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        Services\Utils\UtilsProvider::class,
        Services\OAuth2\OAuth2ServiceProvider::class,
        Services\OpenId\OpenIdProvider::class,
        Auth\AuthenticationServiceProvider::class,
        Services\ServicesProvider::class,
        Strategies\StrategyProvider::class,
        OAuth2\OAuth2ServiceProvider::class,
        OpenId\OpenIdServiceProvider::class,
        \Providers\OAuth2\ClientAuthContextValidatorFactoryProvider::class,
        Greggilbert\Recaptcha\RecaptchaServiceProvider::class,
        Sichikawa\LaravelSendgridDriver\SendgridTransportServiceProvider::class,
        // Doctrine ORM
        LaravelDoctrine\ORM\DoctrineServiceProvider::class,
        // Doctrine Extensions
        LaravelDoctrine\Extensions\GedmoExtensionsServiceProvider::class,
        // Doctrine Migrations
        LaravelDoctrine\Migrations\MigrationsServiceProvider::class,
        // Doctrine Beberlei (Query/Type) extensions install them:
        LaravelDoctrine\Extensions\BeberleiExtensionsServiceProvider::class,
        \App\Models\Utils\MySQLExtensionsServiceProvider::class,
        \App\libs\Utils\FileSystem\SwiftServiceProvider::class,
        // remove 'Laravel\Socialite\SocialiteServiceProvider',
        \SocialiteProviders\Manager\ServiceProvider::class, // add
        \App\libs\Utils\Html\HtmlServiceProvider::class,
        App\libs\Utils\Doctrine\DoctrineCacheServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [
        'App'       => Illuminate\Support\Facades\App::class,
        'Artisan'   => Illuminate\Support\Facades\Artisan::class,
        'Auth'      => Illuminate\Support\Facades\Auth::class,
        'Blade'     => Illuminate\Support\Facades\Blade::class,
        'Cache'     => Illuminate\Support\Facades\Cache::class,
        'Config'    => Illuminate\Support\Facades\Config::class,
        'Cookie'    => Illuminate\Support\Facades\Cookie::class,
        'Crypt'     => Illuminate\Support\Facades\Crypt::class,
        'DB'        => Illuminate\Support\Facades\DB::class,
        'Eloquent'  => Illuminate\Database\Eloquent\Model::class,
        'Event'     => Illuminate\Support\Facades\Event::class,
        'File'      => Illuminate\Support\Facades\File::class,
        'Gate'      => Illuminate\Support\Facades\Gate::class,
        'Hash'      => Illuminate\Support\Facades\Hash::class,
        'Lang'      => Illuminate\Support\Facades\Lang::class,
        'Log'       => Illuminate\Support\Facades\Log::class,
        'Mail'      => Illuminate\Support\Facades\Mail::class,
        'Password'  => Illuminate\Support\Facades\Password::class,
        'Queue'     => Illuminate\Support\Facades\Queue::class,
        'Redirect'  => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Recaptcha' => Kuttumiah\Recaptcha\Facades\Recaptcha::class,
        'ServerConfigurationService' => \Services\Facades\ServerConfigurationService::class,
        'ExternalUrlService' => \Services\Facades\ExternalUrlService::class,
        // Doctrine ORM Facades
        'EntityManager' => LaravelDoctrine\ORM\Facades\EntityManager::class,
        'Registry' => LaravelDoctrine\ORM\Facades\Registry::class,
        'Doctrine' => LaravelDoctrine\ORM\Facades\Doctrine::class,
        'Socialite' => Laravel\Socialite\Facades\Socialite::class,
    ],

    'version'     => env('APP_VERSION', 'XX.XX.XX'),
    'app_name'    => env('APP_NAME', 'OpenStackID'),
    'tenant_name' => env('TENANT_NAME', 'Open Infrastructure'),
    'logo_url'    => env('LOGO_URL', '/assets/img/openstack-logo-full.svg'),
    'email_url'    => env('EMAIL_URL', '/assets/img/openstack-logo-full.svg'),
    'tenant_favicon' => env('TENANT_FAV_ICON_URL', '/assets/img/favicon-32x32.png'),
    'help_email' => env('HELP_EMAIL', 'support@openstack.org'),
    'code_of_conduct_link' => env("CODE_OF_CONDUCT_LINK","https://www.openstack.org/legal/community-code-of-conduct"),
    'app_info' => env("APP_INFO_TEXT"),
    "homepage_info" => env("APP_HOME_PAGE_INFO_TEXT", "Once you\'re signed in, you can manage your trusted sites, change your settings and more."),
    "show_public_profile_show_photo" => env('SHOW_PUBLIC_PROFILE_SHOW_PHOTO_CHECKBOX', true),
    "show_info_banner"      => env('SHOW_INFO_BANNER', 0),
    "info_banner_content"   => env('INFO_BANNER_CONTENT'),
    "default_profile_image" => env('DEFAULT_PROFILE_IMAGE', null),
];
