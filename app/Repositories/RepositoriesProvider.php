<?php namespace Repositories;
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

use App\libs\Auth\Models\SpamEstimatorFeed;
use App\libs\Auth\Models\UserRegistrationRequest;
use App\libs\Auth\Repositories\IBannedIPRepository;
use App\libs\Auth\Repositories\IGroupRepository;
use App\libs\Auth\Repositories\ISpamEstimatorFeedRepository;
use App\libs\Auth\Repositories\IUserExceptionTrailRepository;
use App\libs\Auth\Repositories\IUserPasswordResetRequestRepository;
use App\libs\Auth\Repositories\IUserRegistrationRequestRepository;
use App\libs\Auth\Repositories\IWhiteListedIPRepository;
use App\libs\OAuth2\Repositories\IOAuth2TrailExceptionRepository;
use App\Models\Repositories\IDisqusSSOProfileRepository;
use App\Models\Repositories\IRocketChatSSOProfileRepository;
use App\Models\Repositories\IStreamChatSSOProfileRepository;
use App\Models\SSO\DisqusSSOProfile;
use App\Models\SSO\RocketChatSSOProfile;
use App\Models\SSO\StreamChat\StreamChatSSOProfile;
use App\Repositories\IServerConfigurationRepository;
use App\Repositories\IServerExtensionRepository;
use Auth\Group;
use Auth\User;
use Auth\UserPasswordResetRequest;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Models\BannedIP;
use Models\OAuth2\AccessToken;
use Models\OAuth2\Api;
use Models\OAuth2\ApiEndpoint;
use Models\OAuth2\ApiScope;
use Models\OAuth2\ApiScopeGroup;
use Models\OAuth2\Client;
use Models\OAuth2\ClientPublicKey;
use Models\OAuth2\OAuth2TrailException;
use Models\OAuth2\RefreshToken;
use Models\OAuth2\ResourceServer;
use Models\OAuth2\ServerPrivateKey;
use Models\OpenId\OpenIdAssociation;
use Models\OpenId\OpenIdTrustedSite;
use Models\OpenId\ServerExtension;
use Models\ServerConfiguration;
use Models\UserExceptionTrail;
use Models\WhiteListedIP;
use OAuth2\Repositories\IAccessTokenRepository;
use OAuth2\Repositories\IApiEndpointRepository;
use OAuth2\Repositories\IApiRepository;
use OAuth2\Repositories\IApiScopeGroupRepository;
use OAuth2\Repositories\IApiScopeRepository;
use OAuth2\Repositories\IClientPublicKeyRepository;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Repositories\IRefreshTokenRepository;
use OAuth2\Repositories\IResourceServerRepository;
use Auth\Repositories\IUserRepository;
use OAuth2\Repositories\IServerPrivateKeyRepository;
use LaravelDoctrine\ORM\Facades\EntityManager;
use OpenId\Repositories\IOpenIdAssociationRepository;
use OpenId\Repositories\IOpenIdTrustedSiteRepository;

/**
 * Class RepositoriesProvider
 * @package Repositories
 */
final class RepositoriesProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
    }

    public function register()
    {

        App::singleton(IGroupRepository::class,
            function () {
                return EntityManager::getRepository(Group::class);
            }
        );

        App::singleton(IUserPasswordResetRequestRepository::class,
            function () {
                return EntityManager::getRepository(UserPasswordResetRequest::class);
            });

        App::singleton(IServerExtensionRepository::class,
            function () {
                return EntityManager::getRepository(ServerExtension::class);
            }
        );

        App::singleton(IOpenIdTrustedSiteRepository::class,
            function () {
                return EntityManager::getRepository(OpenIdTrustedSite::class);
            }
        );

        App::singleton(IOpenIdAssociationRepository::class,
            function () {
                return EntityManager::getRepository(OpenIdAssociation::class);
            }
        );

        // doctrine repos

        App::singleton(IServerConfigurationRepository::class,
            function () {
                return EntityManager::getRepository(ServerConfiguration::class);
            }
        );

        App::singleton(IUserExceptionTrailRepository::class,
            function () {
                return EntityManager::getRepository(UserExceptionTrail::class);
            }
        );

        App::singleton(IBannedIPRepository::class,
            function () {
                return EntityManager::getRepository(BannedIP::class);
            }
        );

        App::singleton(IWhiteListedIPRepository::class, function () {
            return EntityManager::getRepository(WhiteListedIP::class);
        });

        App::singleton(IUserRepository::class,
            function () {
                return EntityManager::getRepository(User::class);
            }
        );

        App::singleton(
            IResourceServerRepository::class,
            function () {
                return EntityManager::getRepository(ResourceServer::class);
            }
        );

        App::singleton(
            IApiRepository::class,
            function () {
                return EntityManager::getRepository(Api::class);
            }
        );

        App::singleton(
            IApiEndpointRepository::class,
            function () {
                return EntityManager::getRepository(ApiEndpoint::class);
            }
        );

        App::singleton(
            IClientRepository::class,
            function () {
                return EntityManager::getRepository(Client::class);
            }
        );

        App::singleton(
            IAccessTokenRepository::class,
            function () {
                return EntityManager::getRepository(AccessToken::class);
            }
        );

        App::singleton(
            IRefreshTokenRepository::class,
            function () {
                return EntityManager::getRepository(RefreshToken::class);
            }
        );

        App::singleton(
            IApiScopeRepository::class,
            function () {
                return EntityManager::getRepository(ApiScope::class);
            }
        );

        App::singleton(
            IApiScopeGroupRepository::class,
            function () {
                return EntityManager::getRepository(ApiScopeGroup::class);
            }
        );

        App::singleton(
            IOAuth2TrailExceptionRepository::class,
            function () {
                return EntityManager::getRepository(OAuth2TrailException::class);
            }
        );

        App::singleton(
            IClientPublicKeyRepository::class,
            function () {
                return EntityManager::getRepository(ClientPublicKey::class);
            }
        );

        App::singleton(
            IServerPrivateKeyRepository::class,
            function () {
                return EntityManager::getRepository(ServerPrivateKey::class);
            }
        );

        App::singleton(
            IUserRegistrationRequestRepository::class,
            function () {
                return EntityManager::getRepository(UserRegistrationRequest::class);
            }
        );

        App::singleton(
            ISpamEstimatorFeedRepository::class,
            function () {
                return EntityManager::getRepository(SpamEstimatorFeed::class);
            }
        );

        App::singleton(
            IDisqusSSOProfileRepository::class,
            function () {
                return EntityManager::getRepository(DisqusSSOProfile::class);
            }
        );

        App::singleton(
            IRocketChatSSOProfileRepository::class,
            function () {
                return EntityManager::getRepository(RocketChatSSOProfile::class);
            }
        );

        App::singleton(
            IStreamChatSSOProfileRepository::class,
            function () {
                return EntityManager::getRepository(StreamChatSSOProfile::class);
            }
        );

    }

    public function provides()
    {
        return [
            IGroupRepository::class,
            IUserPasswordResetRequestRepository::class,
            IServerExtensionRepository::class,
            IOpenIdTrustedSiteRepository::class,
            IOpenIdAssociationRepository::class,
            IServerConfigurationRepository::class,
            IUserExceptionTrailRepository::class,
            IBannedIPRepository::class,
            IWhiteListedIPRepository::class,
            IUserRepository::class,
            IResourceServerRepository::class,
            IApiRepository::class,
            IApiEndpointRepository::class,
            IClientRepository::class,
            IAccessTokenRepository::class,
            IRefreshTokenRepository::class,
            IApiScopeRepository::class,
            IApiScopeGroupRepository::class,
            IOAuth2TrailExceptionRepository::class,
            IClientPublicKeyRepository::class,
            IServerPrivateKeyRepository::class,
            IUserRegistrationRequestRepository::class,
            ISpamEstimatorFeedRepository::class,
            IDisqusSSOProfileRepository::class,
            IRocketChatSSOProfileRepository::class,
            IStreamChatSSOProfileRepository::class,
        ];
    }
}