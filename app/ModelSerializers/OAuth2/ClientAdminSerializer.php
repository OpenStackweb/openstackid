<?php namespace App\ModelSerializers\OAuth2;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\ModelSerializers\BaseSerializer;
use Illuminate\Support\Facades\Auth;
use Models\OAuth2\Client;
use OAuth2\Models\IClient;
use OAuth2\OAuth2Protocol;

/**
 * Class ClientAdminSerializer
 * @package App\ModelSerializers\OAuth2
 */
final class ClientAdminSerializer extends ClientSerializer
{
    protected static $array_mappings = [
        'DefaultMaxAge' => 'default_max_age:json_int',
        'JWKSUri' => 'jwks_uri:json_string',
        'LogoUri' => 'logo_uri:json_string',
        'LogoutUri' => 'logout_uri:json_string',
        'LogoutSessionRequired' => 'logout_session_required:json_bool',
        'OtpLength' => 'otp_length:json_int',
        'OtpLifetime' => 'otp_lifetime:json_int',
        'PasswordlessEnabled' => 'otp_enabled:json_bool',
        'PolicyUri' => 'policy_uri:json_string',
        'SubjectType' => 'subject_type:json_string',
        'TermOfServiceUri' => 'tos_uri:json_string',
        'Website' => 'website:json_string',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $client = $this->object;
        if (!$client instanceof Client) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        if ($client->getClientType() == IClient::ClientType_Public) {
            $values['pkce_enabled'] = $client->isPKCEEnabled();
        }

        $idTokenResponseInfo = $client->getIdTokenResponseInfo();
        $tokenEndpointAuthInfo = $client->getTokenEndpointAuthInfo();
        $userResponseInfo = $client->getUserInfoResponseInfo();

        $admin_users = [];
        foreach ($client->getAdminUsers() as $admin_user) {
            $admin_users[] = [
                'id' => $admin_user->id,
                'full_name' => "{$admin_user->first_name} {$admin_user->last_name}",
                'email' => $admin_user->email
            ];
        }
        $values['admin_users'] = $admin_users;

        $values['allowed_origins'] = $client->getClientAllowedOrigins();
        $values['can_request_refresh_tokens'] = $client->canRequestRefreshTokens();
        $values['client_name'] = $client->getFriendlyApplicationType();
        $values['contacts'] = $client->getContacts();

        $values['id_token_encrypted_response_alg'] = 'none';
        $alg = $idTokenResponseInfo->getEncryptionKeyAlgorithm();
        if ($alg != null) $values['id_token_encrypted_response_alg'] = $alg->getName();

        $values['id_token_encrypted_response_enc'] = 'none';
        $alg = $idTokenResponseInfo->getEncryptionContentAlgorithm();
        if ($alg != null) $values['id_token_encrypted_response_enc'] = $alg->getName();

        $values['id_token_signed_response_alg'] = 'none';
        $alg = $idTokenResponseInfo->getSigningAlgorithm();
        if ($alg != null) $values['id_token_signed_response_alg'] = $alg->getName();

        $values['is_allowed_to_use_token_endpoint_auth'] = OAuth2Protocol::isClientAllowedToUseTokenEndpointAuth($client);
        $values['logout_use_iframe'] = $client->useLogoutIframe();
        $values['modified_by'] = $client->getEditedByNice();
        $values['owner_name'] = $client->getOwnerNice();
        $values['post_logout_redirect_uris'] = $client->getPostLogoutUris();
        $values['redirect_uris'] = $client->getRedirectUris();
        $values['rotate_refresh_token'] = $client->useRotateRefreshTokenPolicy();

        $values['token_endpoint_auth_method'] = $tokenEndpointAuthInfo->getAuthenticationMethod() ?? 'none';

        $values['token_endpoint_auth_signing_alg'] = 'none';
        $alg = $tokenEndpointAuthInfo->getSigningAlgorithm();
        if ($alg != null) $values['token_endpoint_auth_signing_alg'] = $alg->getName();

        $values['use_refresh_token'] = $client->useRefreshToken();

        $values['userinfo_encrypted_response_alg'] = 'none';
        $alg = $userResponseInfo->getEncryptionKeyAlgorithm();
        if ($alg != null) $values['userinfo_encrypted_response_alg'] = $alg->getName();

        $values['userinfo_encrypted_response_enc'] = 'none';
        $alg = $userResponseInfo->getEncryptionContentAlgorithm();
        if ($alg != null) $values['userinfo_encrypted_response_enc'] = $alg->getName();

        $values['userinfo_signed_response_alg'] = 'none';
        $alg = $userResponseInfo->getSigningAlgorithm();
        if ($alg != null) $values['userinfo_signed_response_alg'] = $alg->getName();

        return $values;
    }
}