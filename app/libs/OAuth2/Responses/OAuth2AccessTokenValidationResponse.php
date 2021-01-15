<?php namespace OAuth2\Responses;
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

use App\ModelSerializers\SerializerRegistry;
use Auth\User;
use OAuth2\Models\IClient;
use OAuth2\OAuth2Protocol;
use Utils\Http\HttpContentType;
/**
 * Class OAuth2AccessTokenValidationResponse
 * @package OAuth2\Responses
 */
class OAuth2AccessTokenValidationResponse extends OAuth2DirectResponse {

    /**
     * @param array|int $access_token
     * @param string $scope
     * @param $audience
     * @param IClient $client
     * @param $expires_in
     * @param User|null $user
     * @param array $allowed_urls
     * @param array $allowed_origins
     */
    public function __construct
    (
        $access_token,
        $scope,
        $audience,
        IClient $client,
        $expires_in,
        User $user = null,
        $allowed_urls = [],
        $allowed_origins = []
    )
    {
        // Successful Responses: A server receiving a valid request MUST send a
        // response with an HTTP status code of 200.
        parent::__construct(self::HttpOkResponse, HttpContentType::Json);
        $this[OAuth2Protocol::OAuth2Protocol_AccessToken]           = $access_token;
        $this[OAuth2Protocol::OAuth2Protocol_ClientId]              = $client->getClientId();
        $this['application_type']                                   = $client->getApplicationType();
        $this[OAuth2Protocol::OAuth2Protocol_TokenType]             = 'Bearer';
        $this[OAuth2Protocol::OAuth2Protocol_Scope]                 = $scope;
        $this[OAuth2Protocol::OAuth2Protocol_Audience]              = $audience;
        $this[OAuth2Protocol::OAuth2Protocol_AccessToken_ExpiresIn] = $expires_in;

        if(!is_null($user))
        {
            // user info if present
            $this[OAuth2Protocol::OAuth2Protocol_UserId] = $user->getId();
            $this['user_identifier']                     = $user->getIdentifier();
            $this['user_email']                          = $user->getEmail();
            $this['user_first_name']                     = $user->getFirstName();
            $this['user_last_name']                      = $user->getLastName();
            $this['user_language']                       = $user->getLanguage();
            $this['user_country']                        = $user->getCountry();
            $this['user_email_verified']                 = $user->isEmailVerified();
            $this['user_pic']                            = $user->getPic();
            // default empty value
            $user_groups                                = [];
            foreach ($user->getGroups() as $group){
                $user_groups[] = SerializerRegistry::getInstance()->getSerializer($group)->serialize();
            }

            $this['user_groups'] = $user_groups;
        }

        if(count($allowed_urls)){
            $this['allowed_return_uris'] = implode(' ', $allowed_urls);
        }

        if(count($allowed_origins)){
            $this['allowed_origins'] = implode(' ', $allowed_origins);
        }
    }
} 