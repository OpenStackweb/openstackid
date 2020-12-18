<?php namespace Services\OAuth2\ResourceServer;
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
use Auth\Repositories\IUserRepository;
use Auth\User;
use Exception;
use Illuminate\Support\Facades\Log;
use jwt\impl\JWTClaimSet;
use jwt\JWTClaim;
use OAuth2\AddressClaim;
use OAuth2\IResourceServerContext;
use OAuth2\Repositories\IClientRepository;
use OAuth2\ResourceServer\IUserService;
use OAuth2\ResourceServer\OAuth2ProtectedService;
use OAuth2\StandardClaims;
use OpenId\Services\IUserService as IAPIUserService;
use models\exceptions\EntityNotFoundException;
use utils\json_types\JsonValue;
use utils\json_types\StringOrURI;
use Utils\Services\IAuthService;
use Utils\Services\ILogService;
use Utils\Services\IServerConfigurationService;

/**
 * Class UserService
 * OAUTH2 Protected Endpoint
 * @package Services\OAuth2\ResourceServer
 */
final class UserService extends OAuth2ProtectedService implements IUserService
{
    /**
     * @var IAPIUserService
     */
    private $user_service;
    /**
     * @var IServerConfigurationService
     */
    private $configuration_service;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var IAuthService
     */
    private $auth_service;

    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * UserService constructor.
     * @param IAPIUserService $user_service
     * @param IResourceServerContext $resource_server_context
     * @param IServerConfigurationService $configuration_service
     * @param ILogService $log_service
     * @param IClientRepository $client_repository
     * @param IUserRepository $user_repository
     * @param IAuthService $auth_service
     */
    public function __construct
    (
        IAPIUserService $user_service,
        IResourceServerContext $resource_server_context,
        IServerConfigurationService $configuration_service,
        ILogService $log_service,
        IClientRepository $client_repository,
        IUserRepository $user_repository,
        IAuthService $auth_service
    )
    {
        parent::__construct($resource_server_context, $log_service);

        $this->user_service = $user_service;
        $this->configuration_service = $configuration_service;
        $this->client_repository = $client_repository;
        $this->auth_service = $auth_service;
        $this->user_repository = $user_repository;
    }

    /**
     * Get Current user info
     * @return array
     * @throws Exception
     */
    public function getCurrentUserInfo()
    {
        $data = [];
        try {
            Log::debug("UserService::getCurrentUserInfo");
            $current_user_id = $this->resource_server_context->getCurrentUserId();

            Log::debug(sprintf("UserService::getCurrentUserInfo current_user_id %s", $current_user_id));
            if (is_null($current_user_id)) {
                throw new Exception('me is no set!.');
            }

            $current_user = $this->user_repository->getById($current_user_id);
            if (is_null($current_user)) throw new EntityNotFoundException();

            if (!$current_user instanceof User) throw new EntityNotFoundException();

            $scopes = $this->resource_server_context->getCurrentScope();

            if (in_array(self::UserProfileScope_Address, $scopes)) {
                // Address Claims
                Log::debug(sprintf("UserService::getCurrentUserInfo current_user_id %s address", $current_user_id));
                $data[AddressClaim::Country] = $current_user->getCountry();
                $data[AddressClaim::StreetAddress] = $current_user->getStreetAddress();
                $data[AddressClaim::Address1] = $current_user->getAddress1();
                $data[AddressClaim::Address2] = $current_user->getAddress2();
                $data[AddressClaim::PostalCode] = $current_user->getPostalCode();
                $data[AddressClaim::Region] = $current_user->getRegion();
                $data[AddressClaim::Locality] = $current_user->getLocality();
            }
            if (in_array(self::UserProfileScope_Profile, $scopes)) {
                Log::debug(sprintf("UserService::getCurrentUserInfo current_user_id %s profile", $current_user_id));
                // Profile Claims
                $data[StandardClaims::Name] = $current_user->getFullName();
                $data[StandardClaims::GivenName] = $current_user->getFirstName();
                $data[StandardClaims::FamilyName] = $current_user->getLastName();
                $data[StandardClaims::PhoneNumber] = $current_user->getPhoneNumber();
                $data[StandardClaims::PhoneNumberVerified] = false;
                $data[StandardClaims::NickName] = $current_user->getIdentifier();
                $data[StandardClaims::SubjectIdentifier] = $current_user->getAuthIdentifier();
                $data[StandardClaims::Picture] = $current_user->getPic();
                $data[StandardClaims::Birthdate] = $current_user->getDateOfBirth();
                $data[StandardClaims::Gender] = $current_user->getGender();
                $data[StandardClaims::GenderSpecify] = $current_user->getGenderSpecify();
                $data[StandardClaims::Locale] = $current_user->getLanguage();
                $data[StandardClaims::Bio] = $current_user->getBio();
                $data[StandardClaims::StatementOfInterest] = $current_user->getStatementOfInterest();
                $data[StandardClaims::Irc] = $current_user->getIrc();
                $data[StandardClaims::LinkedInProfile] = $current_user->getLinkedInProfile();
                $data[StandardClaims::GitHubUser] = $current_user->getGithubUser();
                $data[StandardClaims::WeChatUser] = $current_user->getWechatUser();
                $data[StandardClaims::TwitterName] = $current_user->getTwitterName();
                $data[StandardClaims::Company] = $current_user->getCompany();
                $data[StandardClaims::ShowPicture] = $current_user->isPublicProfileShowPhoto();
                $data[StandardClaims::ShowFullName] = $current_user->isPublicProfileShowFullname();
                $user_groups = [];

                foreach ($current_user->getGroups() as $group) {
                    $user_groups[] = SerializerRegistry::getInstance()->getSerializer($group)->serialize();
                }

                $data[StandardClaims::Groups] = $user_groups;
            }
            if (in_array(self::UserProfileScope_Email, $scopes)) {
                Log::debug(sprintf("UserService::getCurrentUserInfo current_user_id %s email", $current_user_id));
                // Email Claim
                $data[StandardClaims::Email] = $current_user->getEmail();
                $data[StandardClaims::ShowEmail] = $current_user->isPublicProfileShowEmail();
                $data[StandardClaims::SecondEmail] = $current_user->getSecondEmail();
                $data[StandardClaims::ThirdEmail] = $current_user->getThirdEmail();
                $data[StandardClaims::EmailVerified] = $current_user->isEmailVerified();
            }
        } catch (Exception $ex) {
            $this->log_service->error($ex);
            throw $ex;
        }

        return $data;
    }

    /**
     * @param JWTClaimSet $claim_set
     * @param User $user
     * @return JWTClaimSet
     * @throws \jwt\exceptions\ClaimAlreadyExistsException
     */
    public static function populateProfileClaims(JWTClaimSet $claim_set, User $user): JWTClaimSet
    {
        // Profile Claims
        $claim_set->addClaim(new JWTClaim(StandardClaims::Name, new StringOrURI($user->getFullName())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::GivenName, new StringOrURI($user->getFirstName())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::PreferredUserName, new StringOrURI($user->getNickName())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::FamilyName, new StringOrURI($user->getLastName())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::NickName, new StringOrURI($user->getNickName())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::Picture, new StringOrURI($user->getPic())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::Birthdate, new StringOrURI($user->getDateOfBirthNice())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::Gender, new StringOrURI($user->getGender())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::GenderSpecify, new StringOrURI($user->getGenderSpecify())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::Locale, new StringOrURI($user->getLanguage())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::Bio, new StringOrURI($user->getBio())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::StatementOfInterest, new StringOrURI($user->getStatementOfInterest())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::Irc, new StringOrURI($user->getIrc())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::GitHubUser, new StringOrURI($user->getGithubUser())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::WeChatUser, new StringOrURI($user->getWechatUser())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::TwitterName, new StringOrURI($user->getTwitterName())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::LinkedInProfile, new StringOrURI($user->getLinkedInProfile())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::ShowPicture, new JsonValue($user->isPublicProfileShowPhoto())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::ShowFullName, new JsonValue($user->isPublicProfileShowFullname())));

        $user_groups = [];

        foreach ($user->getGroups() as $group) {
            $user_groups[] = SerializerRegistry::getInstance()->getSerializer($group)->serialize();
        }

        $claim_set->addClaim(new JWTClaim(StandardClaims::Groups, new JsonValue($user_groups)));

        return $claim_set;
    }

    /**
     * @param JWTClaimSet $claim_set
     * @param User $user
     * @return JWTClaimSet
     * @throws \jwt\exceptions\ClaimAlreadyExistsException
     */
    public static function populateEmailClaims(JWTClaimSet $claim_set, User $user): JWTClaimSet
    {
        $claim_set->addClaim(new JWTClaim(StandardClaims::Email, new StringOrURI($user->getEmail())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::SecondEmail, new StringOrURI($user->getSecondEmail())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::ThirdEmail, new StringOrURI($user->getThirdEmail())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::EmailVerified, new JsonValue($user->isEmailVerified())));
        $claim_set->addClaim(new JWTClaim(StandardClaims::ShowEmail, new JsonValue($user->isPublicProfileShowEmail())));
        return $claim_set;
    }

    /**
     * @param JWTClaimSet $claim_set
     * @param User $user
     * @return JWTClaimSet
     * @throws \jwt\exceptions\ClaimAlreadyExistsException
     */
    public static function populateAddressClaims(JWTClaimSet $claim_set, User $user): JWTClaimSet
    {
        // Address Claims
        $address = [];
        $address[AddressClaim::Country] = $user->getCountry();
        $address[AddressClaim::StreetAddress] = $user->getStreetAddress();
        $address[AddressClaim::Address1] = $user->getAddress1();
        $address[AddressClaim::Address2] = $user->getAddress2();
        $address[AddressClaim::PostalCode] = $user->getPostalCode();
        $address[AddressClaim::Region] = $user->getRegion();
        $address[AddressClaim::Locality] = $user->getLocality();
        $address[AddressClaim::Formatted] = $user->getFormattedAddress();

        $claim_set->addClaim(new JWTClaim(StandardClaims::Address, new JsonValue($address)));

        return $claim_set;
    }

    /**
     * @return JWTClaimSet
     * @throws Exception
     */
    public function getCurrentUserInfoClaims()
    {
        try {

            $current_user_id = $this->resource_server_context->getCurrentUserId();
            $client_id = $this->resource_server_context->getCurrentClientId();
            $client = $this->client_repository->getClientById($client_id);

            if (is_null($current_user_id)) {
                throw new Exception('me is no set!.');
            }

            $current_user = $this->user_repository->getById($current_user_id);
            if (is_null($current_user)) throw new EntityNotFoundException();
            if (!$current_user instanceof User) throw new EntityNotFoundException();
            $scopes = $this->resource_server_context->getCurrentScope();

            $claim_set = new JWTClaimSet
            (
                null,
                $sub = new StringOrURI
                (
                    $this->auth_service->wrapUserId
                    (
                        $current_user->getId(),
                        $client
                    )
                ),
                $aud = new StringOrURI($client_id)

            );

            if (in_array(self::UserProfileScope_Address, $scopes)) {
                self::populateAddressClaims($claim_set, $current_user);
            }
            if (in_array(self::UserProfileScope_Profile, $scopes)) {
                self::populateProfileClaims($claim_set, $current_user);
            }
            if (in_array(self::UserProfileScope_Email, $scopes)) {
                // Email Address Claim
                self::populateEmailClaims($claim_set, $current_user);
            }
        } catch (Exception $ex) {
            $this->log_service->error($ex);
            throw $ex;
        }
        return $claim_set;
    }
}