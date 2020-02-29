<?php namespace App\Models\OAuth2\Factories;
/**
 * Copyright 2019 OpenStack Foundation
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

use App\libs\Utils\URLUtils;
use Illuminate\Support\Facades\App;
use Models\OAuth2\Client;
use Models\OAuth2\ResourceServer;
use OAuth2\Models\IClient;
use OAuth2\OAuth2Protocol;
use OAuth2\Repositories\IApiScopeRepository;
/**
 * Class ClientFactory
 * @package App\Models\OAuth2\Factories
 */
final class ClientFactory
{
    /**
     * @param array $payload
     * @return Client
     * @throws \Exception
     */
    public static function build(array $payload):Client
    {
        $scope_repository = App::make(IApiScopeRepository::class);
        $client = self::populate(new Client, $payload);
        $client->setActive(true);
        //add default scopes
        foreach ($scope_repository->getDefaults() as $default_scope) {
            if
            (
                $default_scope->getName() === OAuth2Protocol::OfflineAccess_Scope
                && !$client->canRequestRefreshTokens()
            ) {
                continue;
            }
            $client->addScope($default_scope);
        }

        if ($client->getClientType() !== IClient::ClientType_Confidential) {
            $client->setTokenEndpointAuthMethod(OAuth2Protocol::TokenEndpoint_AuthMethod_None);
        }

        return $client;
    }

    /**
     * @param Client $client
     * @param array $payload
     * @return Client
     * @throws \Exception
     */
    public static function populate(Client $client, array $payload):Client
    {
        $fields_to_uri_normalize = [
            'post_logout_redirect_uris',
            'logout_uri',
            'policy_uri',
            'jwks_uri',
            'tos_uri',
            'logo_uri',
            'redirect_uris',
            'allowed_origins'
        ];

        foreach($fields_to_uri_normalize as $field){
            if(!isset($payload[$field])) continue;
            $value = $payload[$field];
            if (empty($value)) continue;
            $urls = explode(',', $value);
            $normalized_uris = '';
            foreach ($urls as $url) {
                $url = URLUtils::normalizeUrl($url);
                if (!empty($normalized_uris)) {
                    $normalized_uris .= ',';
                }
                $normalized_uris .= $url;
            }
            $payload[$field] = $normalized_uris;
        }

        if(isset($payload['owner']))
            $client->setOwner($payload['owner']);

        if(isset($payload['app_name']))
            $client->setAppName(trim($payload['app_name']));

        if(isset($payload['application_type']))
            $client->setApplicationType(trim($payload['application_type']));

        if(isset($payload['app_description']))
            $client->setAppDescription(trim($payload['app_description']));

        if(isset($payload['website']))
            $client->setWebsite(trim($payload['website']));

        if(isset($payload['active']))
            $client->setActive(boolval($payload['active']));

        if(isset($payload['locked']))
            $client->setLocked(boolval($payload['locked']));

        if(isset($payload['use_refresh_token']))
            $client->setUseRefreshToken(boolval($payload['use_refresh_token']));

        if(isset($payload['rotate_refresh_token']))
            $client->setRotateRefreshToken(boolval($payload['rotate_refresh_token']));

        if(isset($payload['contacts']))
            $client->setContacts(trim($payload['contacts']));

        if(isset($payload['logo_uri']))
            $client->setLogoUri(trim($payload['logo_uri']));

        if(isset($payload['tos_uri']))
            $client->setTosUri(trim($payload['tos_uri']));

        if(isset($payload['post_logout_redirect_uris']))
            $client->setPostLogoutRedirectUris(trim($payload['post_logout_redirect_uris']));

        if(isset($payload['logout_uri']))
            $client->setLogoutUri(trim($payload['logout_uri']));

        if(isset($payload['policy_uri']))
            $client->setPolicyUri(trim($payload['policy_uri']));

        if(isset($payload['jwks_uri']))
            $client->setJwksUri(trim($payload['jwks_uri']));

        if(isset($payload['default_max_age']))
            $client->setDefaultMaxAge(intval($payload['default_max_age']));

        if(isset($payload['logout_session_required']))
            $client->setLogoutSessionRequired(boolval($payload['logout_session_required']));

        if(isset($payload['logout_use_iframe']))
            $client->setLogoutUseIframe(boolval($payload['logout_use_iframe']));

        if(isset($payload['require_auth_time']))
            $client->setRequireAuthTime(boolval($payload['require_auth_time']));

        if(isset($payload['token_endpoint_auth_method']))
            $client->setTokenEndpointAuthMethod(trim($payload['token_endpoint_auth_method']));

        if(isset($payload['token_endpoint_auth_signing_alg']))
            $client->setTokenEndpointAuthSigningAlg(trim($payload['token_endpoint_auth_signing_alg']));

        if(isset($payload['subject_type']))
            $client->setSubjectType(trim($payload['subject_type']));

        if(isset($payload['userinfo_signed_response_alg']))
            $client->setUserinfoSignedResponseAlg(trim($payload['userinfo_signed_response_alg']));

        if(isset($payload['userinfo_encrypted_response_alg']))
            $client->setUserinfoEncryptedResponseAlg(trim($payload['userinfo_encrypted_response_alg']));

        if(isset($payload['userinfo_encrypted_response_enc']))
            $client->setUserinfoEncryptedResponseEnc(trim($payload['userinfo_encrypted_response_enc']));

        if(isset($payload['id_token_signed_response_alg']))
            $client->setIdTokenSignedResponseAlg(trim($payload['id_token_signed_response_alg']));

        if(isset($payload['id_token_encrypted_response_alg']))
            $client->setIdTokenEncryptedResponseAlg(trim($payload['id_token_encrypted_response_alg']));

        if(isset($payload['id_token_encrypted_response_enc']))
            $client->setIdTokenEncryptedResponseEnc(trim($payload['id_token_encrypted_response_enc']));

        if(isset($payload['redirect_uris']))
            $client->setRedirectUris(trim($payload['redirect_uris']));

        if(isset($payload['allowed_origins']))
            $client->setAllowedOrigins(trim($payload['allowed_origins']));

        if(isset($payload['client_id'])){
            $client->setClientId(trim($payload['client_id']));
        }

        if(isset($payload['client_secret'])){
            $client->setClientSecret(trim($payload['client_secret']));
        }

        if(isset($payload['client_secret_expires_at'])){
            $client_secret_expires_at = $payload['client_secret_expires_at'];
            if(is_int($client_secret_expires_at))
                $client_secret_expires_at = new \DateTime("@$client_secret_expires_at");
            $client->setClientSecretExpiresAt($client_secret_expires_at);
        }

        if(isset($payload['resource_server']) && $payload['resource_server'] instanceof ResourceServer){
            $resource_server = $payload['resource_server'];
            $client->setResourceServer($resource_server);
        }

        return $client;
    }
}