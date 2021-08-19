<?php namespace Services;
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
use App\libs\Auth\Repositories\IUserExceptionTrailRepository;
use App\Services\Apis\IRocketChatAPI;
use App\Services\Apis\RocketChatAPI;
use App\Services\Auth\DisqusSSOService;
use App\Services\Auth\GroupService;
use App\Services\Auth\IDisqusSSOService;
use App\Services\Auth\IGroupService;
use App\Services\Auth\IRocketChatSSOService;
use App\Services\Auth\IUserIdentifierGeneratorService;
use App\Services\Auth\IUserService;
use App\Services\Auth\RocketChatSSOService;
use App\Services\Auth\StreamChatSSOService;
use App\Services\Auth\IStreamChatSSOService;
use App\Services\Auth\UserIdentifierGeneratorService;
use App\Services\Auth\UserService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Services\SecurityPolicies\AuthorizationCodeRedeemPolicy;
use Services\SecurityPolicies\OAuth2SecurityPolicy;
use Utils\Db\ITransactionService;
use Utils\Services\UtilsServiceCatalog;
use Services\Utils\CheckPointService;
use Illuminate\Support\Facades\App;
/**
 * Class ServicesProvider
 * @package Services
 */
final class ServicesProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(){
    }

    public function register(){

        App::singleton(IUserIdentifierGeneratorService::class, UserIdentifierGeneratorService::class);
        App::singleton(\Services\IUserActionService::class, \Services\UserActionService::class);
        App::singleton(\Services\SecurityPolicies\DelayCounterMeasure::class,  \Services\SecurityPolicies\DelayCounterMeasure::class);
        App::singleton(\Services\SecurityPolicies\LockUserCounterMeasure::class, \Services\SecurityPolicies\LockUserCounterMeasure::class);
        App::singleton(\Services\SecurityPolicies\RevokeAuthorizationCodeRelatedTokens::class,  \Services\SecurityPolicies\RevokeAuthorizationCodeRelatedTokens::class);
        App::singleton(\Services\SecurityPolicies\BlacklistSecurityPolicy::class,  \Services\SecurityPolicies\BlacklistSecurityPolicy::class);
        App::singleton(\Services\SecurityPolicies\LockUserSecurityPolicy::class,  \Services\SecurityPolicies\LockUserSecurityPolicy::class);
        App::singleton(\Services\SecurityPolicies\OAuth2LockClientCounterMeasure::class,  \Services\SecurityPolicies\OAuth2LockClientCounterMeasure::class);
        App::singleton(\Services\SecurityPolicies\OAuth2SecurityPolicy::class, \Services\SecurityPolicies\OAuth2SecurityPolicy::class);
        App::singleton(\Services\SecurityPolicies\AuthorizationCodeRedeemPolicy::class,\Services\SecurityPolicies\AuthorizationCodeRedeemPolicy::class);

        App::singleton(UtilsServiceCatalog::CheckPointService,
            function(){
                //set security policies
                $delay_counter_measure = App::make(\Services\SecurityPolicies\DelayCounterMeasure::class);

                $blacklist_security_policy = App::make(\Services\SecurityPolicies\BlacklistSecurityPolicy::class);
                $blacklist_security_policy->setCounterMeasure($delay_counter_measure);

                $revoke_tokens_counter_measure = App::make(\Services\SecurityPolicies\RevokeAuthorizationCodeRelatedTokens::class);

                $authorization_code_redeem_Policy = App::make(\Services\SecurityPolicies\AuthorizationCodeRedeemPolicy::class);
                $authorization_code_redeem_Policy->setCounterMeasure($revoke_tokens_counter_measure);

                $lock_user_counter_measure = App::make(\Services\SecurityPolicies\LockUserCounterMeasure::class);

                $lock_user_security_policy = App::make(\Services\SecurityPolicies\LockUserSecurityPolicy::class);
                $lock_user_security_policy->setCounterMeasure($lock_user_counter_measure);

                $oauth2_lock_client_counter_measure = App::make(\Services\SecurityPolicies\OAuth2LockClientCounterMeasure::class);
                $oauth2_security_policy             = App::make(\Services\SecurityPolicies\OAuth2SecurityPolicy::class);
                $oauth2_security_policy->setCounterMeasure($oauth2_lock_client_counter_measure);

                $checkpoint_service = new CheckPointService
                (
                    App::make(IUserExceptionTrailRepository::class),
                    App::make(ITransactionService::class)
                );

                $checkpoint_service->addPolicy($blacklist_security_policy);
                $checkpoint_service->addPolicy($lock_user_security_policy);
                $checkpoint_service->addPolicy($authorization_code_redeem_Policy);
                $checkpoint_service->addPolicy($oauth2_security_policy);
                return $checkpoint_service;
            });

        App::singleton(IUserService::class, UserService::class);
        App::singleton(IGroupService::class, GroupService::class);
        App::singleton(IDisqusSSOService::class, DisqusSSOService::class);
        App::singleton(IRocketChatSSOService::class, RocketChatSSOService::class);
        App::singleton(IRocketChatAPI::class, RocketChatAPI::class);
        App::singleton(IStreamChatSSOService::class, StreamChatSSOService::class);
    }

    public function provides()
    {
        return [
            IUserActionService::class,
            \Services\SecurityPolicies\DelayCounterMeasure::class,
            \Services\SecurityPolicies\LockUserCounterMeasure::class,
            \Services\SecurityPolicies\RevokeAuthorizationCodeRelatedTokens::class,
            \Services\SecurityPolicies\BlacklistSecurityPolicy::class,
            \Services\SecurityPolicies\LockUserSecurityPolicy::class,
            \Services\SecurityPolicies\OAuth2LockClientCounterMeasure::class,
            IUserService::class,
            IGroupService::class,
            OAuth2SecurityPolicy::class,
            AuthorizationCodeRedeemPolicy::class,
            UtilsServiceCatalog::CheckPointService,
            IUserService::class,
            IDisqusSSOService::class,
            IRocketChatSSOService::class,
            IRocketChatAPI::class,
            IStreamChatSSOService::class,
            IUserIdentifierGeneratorService::class,
        ];
    }
}