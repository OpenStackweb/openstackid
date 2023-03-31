<?php namespace App\Providers;
/**
 * Copyright 2016 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use App\Events\OAuth2ClientLocked;
use App\Events\UserEmailUpdated;
use App\Events\UserLocked;
use App\Events\UserPasswordResetRequestCreated;
use App\Events\UserPasswordResetSuccessful;
use App\Events\UserSpamStateUpdated;
use App\Jobs\PublishUserCreated;
use App\libs\Auth\Repositories\IUserPasswordResetRequestRepository;
use App\Mail\UserLockedEmail;
use App\Mail\UserPasswordResetMail;
use Auth\User;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Mail\UserEmailVerificationSuccess;
use App\Services\Auth\IUserService;
use Auth\Repositories\IUserRepository;
use Illuminate\Support\Facades\App;
use App\Events\UserCreated;
use App\Events\UserEmailVerified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Models\OAuth2\Client;
use OAuth2\Repositories\IClientRepository;
/**
 * Class EventServiceProvider
 * @package App\Providers
 */
final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Illuminate\Database\Events\QueryExecuted' => [
        ],
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            // ... other providers
            'SocialiteProviders\\Facebook\\FacebookExtendSocialite@handle',
            'SocialiteProviders\\Google\\GoogleExtendSocialite@handle',
            'SocialiteProviders\\Apple\\AppleExtendSocialite@handle',
            'SocialiteProviders\\LinkedIn\\LinkedInExtendSocialite@handle',
            'SocialiteProviders\\Okta\\OktaExtendSocialite@handle'
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Event::listen(UserEmailVerified::class, function($event)
        {
            $service   = App::make(IUserService::class);
            if(is_null($service) || !$service instanceof IUserService) return;
            $service->sendSuccessfulVerificationEmail($event->getUserId());
        });

        Event::listen(UserCreated::class, function($event)
        {
            // new user created
            $service = App::make(IUserService::class);
            if(is_null($service) || !$service instanceof IUserService) return;
            $service->initializeUser($event->getUserId());
        });

        Event::listen(UserSpamStateUpdated::class, function($event)
        {
            $repository   = App::make(IUserRepository::class);
            $user         = $repository->getById($event->getUserId());
            if(is_null($user)) return;
            if(! $user instanceof User) return;
            $user_service = App::make(IUserService::class);
            $user_service->recalculateUserSpamType($user);
        });

        Event::listen(UserEmailUpdated::class, function($event)
        {
            $repository   = App::make(IUserRepository::class);
            $user         = $repository->getById($event->getUserId());
            if(is_null($user)) return;
            if(! $user instanceof User) return;
            $user_service = App::make(IUserService::class);
            $user_service->sendVerificationEmail($user);
        });

        Event::listen(UserPasswordResetRequestCreated::class, function($event){
            $repository   = App::make(IUserPasswordResetRequestRepository::class);
            $request      = $repository->find($event->getUserId());
            if(is_null($request)) return;
        });

        Event::listen(UserLocked::class, function($event){
            $repository   = App::make(IUserRepository::class);
            $user         = $repository->getById($event->getUserId());
            if(is_null($user)) return;
            if(!$user instanceof User) return;

            $support_email = Config::get("mail.support_email", null);
            if(!empty($support_email)) {
                $attempts = $user->getLoginFailedAttempt();
                Mail::queue(new UserLockedEmail($user, $support_email, $attempts));
            }
        });

        Event::listen(UserPasswordResetSuccessful::class, function($event){
            $repository   = App::make(IUserRepository::class);
            $user         = $repository->getById($event->getUserId());
            if(is_null($user)) return;
            if(!$user instanceof User) return;
            Mail::queue(new UserPasswordResetMail($user));
        });

        Event::listen(OAuth2ClientLocked::class, function($event){
            $repository   = App::make(IClientRepository::class);
            $client       = $repository->getClientById($event->getClientId());
            if(is_null($client)) return;
            if(!$client instanceof Client) return;
            Mail::queue(new \App\Mail\OAuth2ClientLocked($client));
        });

        Event::listen(\Illuminate\Mail\Events\MessageSending::class, function($event){
            $devEmail = env('DEV_EMAIL_TO', null);
            if(in_array(App::environment(), ['local','dev','testing']) && !empty($devEmail)){
                $event->message->setTo(explode(",", $devEmail));
            }
            return true;
        });

        // check this one here https://github.com/laravel/framework/issues/33238#issuecomment-897063577
        Event::listen(MigrationsStarted::class, function (){
            if (config('databases.allow_disabled_pk')) {
                DB::statement('SET SESSION sql_require_primary_key=0');
            }
        });

        Event::listen(MigrationsEnded::class, function (){
            if (config('databases.allow_disabled_pk')) {
                DB::statement('SET SESSION sql_require_primary_key=1');
            }
        });
    }
}
