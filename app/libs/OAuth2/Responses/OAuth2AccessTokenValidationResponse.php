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
class OAuth2AccessTokenValidationResponse extends OAuth2DirectResponse
{

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
        $this[OAuth2Protocol::OAuth2Protocol_AccessToken] = $access_token;
        $this[OAuth2Protocol::OAuth2Protocol_ClientId] = $client->getClientId();
        $this['application_type'] = $client->getApplicationType();
        $this[OAuth2Protocol::OAuth2Protocol_TokenType] = 'Bearer';
        $this[OAuth2Protocol::OAuth2Protocol_Scope] = $scope;
        $this[OAuth2Protocol::OAuth2Protocol_Audience] = $audience;
        $this[OAuth2Protocol::OAuth2Protocol_AccessToken_ExpiresIn] = $expires_in;

        if (!is_null($user)) {
            // user info if present
            $this[OAuth2Protocol::OAuth2Protocol_UserId] = $user->getId();
            $this['user_identifier'] = $user->getIdentifier();
            $this['user_email'] = $user->getEmail();
            $this['user_second_email'] = $user->getSecondEmail();
            $this['user_third_email'] = $user->getThirdEmail();
            $this['user_first_name'] = $user->getFirstName();
            $this['user_last_name'] = $user->getLastName();
            $this['user_language'] = $user->getLanguage();
            $this['user_country'] = $user->getCountry();
            $this['user_email_verified'] = $user->isEmailVerified();
            $this['user_pic'] = $user->getPic();

            if($user->isPublicProfileShowBio()) {
                $this['user_bio'] = $user->getBio();
                $this['user_statement_of_interest'] = $user->getStatementOfInterest();
                $this['user_birthday'] = $user->getDateOfBirthNice();
                $this['user_language'] = $user->getLanguage();
                $this['user_gender'] = $user->getGender();
                $this['user_gender_specify'] = $user->getGenderSpecify();
                $this['user_company'] = $user->getCompany();
                $this['user_job_title'] = $user->getJobTitle();
            }

            if($user->isPublicProfileShowTelephoneNumber()){
                $this['user_phone_number'] = $user->getPhoneNumber();
            }

            if($user->isPublicProfileShowSocialMediaInfo()){
                $this['user_irc'] = $user->getIrc();
                $this['user_linked_in_profile'] = $user->getLinkedInProfile();
                $this['user_github_user'] = $user->getGithubUser();
                $this['user_wechat_user'] = $user->getWechatUser();
                $this['user_twitter_name'] = $user->getTwitterName();
            }

            // permissions
            $this["user_public_profile_show_fullname"] = $user->isPublicProfileShowFullname();
            $this['user_public_profile_show_email'] = $user->isPublicProfileShowEmail();
            $this['user_public_profile_show_photo'] = $user->isPublicProfileShowPhoto();
            $this['user_public_profile_show_bio'] = $user->isPublicProfileShowBio();
            $this['user_public_profile_show_social_media_info'] = $user->isPublicProfileShowSocialMediaInfo();
            $this['user_public_profile_show_telephone_number'] = $user->isPublicProfileShowTelephoneNumber();
            $this['user_public_profile_allow_chat_with_me'] = $user->isPublicProfileAllowChatWithMe();

            // default empty value
            $user_groups = [];
            foreach ($user->getGroups() as $group) {
                $user_groups[] = SerializerRegistry::getInstance()->getSerializer($group)->serialize();
            }

            $this['user_groups'] = $user_groups;
        }

        if (count($allowed_urls)) {
            $this['allowed_return_uris'] = implode(' ', $allowed_urls);
        }

        if (count($allowed_origins)) {
            $this['allowed_origins'] = implode(' ', $allowed_origins);
        }
    }
} 