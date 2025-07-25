<?php namespace Auth;
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

use App\Events\UserCreated;
use App\Events\UserLocked;
use App\Events\UserSpamStateUpdated;
use App\Jobs\AddUserAction;
use App\Jobs\NotifyMonitoredSecurityGroupActivity;
use App\libs\Auth\Models\IGroupSlugs;
use App\libs\Auth\Models\UserRegistrationRequest;
use App\libs\Utils\PunnyCodeHelper;
use App\libs\Utils\TextUtils;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use App\Events\UserEmailVerified;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use models\exceptions\ValidationException;
use Models\OAuth2\ApiScope;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use Models\OAuth2\UserConsent;
use Models\OpenId\OpenIdTrustedSite;
use Models\UserAction;
use models\utils\RandomGenerator;
use OAuth2\Models\IOAuth2User;
use OpenId\Models\IOpenIdUser;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Doctrine\Common\Collections\ArrayCollection;
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PostPersist;
use Doctrine\ORM\Mapping\PreRemove;
use Doctrine\ORM\Mapping\PreUpdate;
use Utils\IPHelper;

/**
 * @package Auth
 */
#[ORM\Table(name: 'users')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineUserRepository::class)]
#[ORM\HasLifecycleCallbacks] // Class User
class User extends BaseEntity
    implements AuthenticatableContract, IOpenIdUser, IOAuth2User, CanResetPassword
{
    use Authenticatable;

    use CanResetPasswordTrait;

    const SpamTypeNone = 'None';
    const SpamTypeSpam = 'Spam';
    const SpamTypeHam  = 'Ham';

    const ValidSpamTypes = [
        self::SpamTypeNone,
        self::SpamTypeSpam,
        self::SpamTypeHam
    ];

    /**
     * @var string
     */
    #[ORM\Column(name: 'identifier', type: 'string')]
    private $identifier;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'active', options: ['default' => 0], type: 'boolean')]
    private $active;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'public_profile_show_photo', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_photo;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'public_profile_show_fullname', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_fullname;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'public_profile_show_email', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_email;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'public_profile_allow_chat_with_me', options: ['default' => 0], type: 'boolean')]
    private $public_profile_allow_chat_with_me;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'public_profile_show_social_media_info', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_social_media_info;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'public_profile_show_bio', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_bio;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'public_profile_show_telephone_number', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_telephone_number;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'last_login_date', type: 'datetime')]
    private $last_login_date;

    /**
     * @var int
     */
    #[ORM\Column(name: 'login_failed_attempt', options: ['unsigned' => true, 'default' => 0], type: 'integer')]
    private $login_failed_attempt;

    /**
     * @var string
     */
    #[ORM\Column(name: 'remember_token', nullable: true, type: 'string')]
    private $remember_token;

    /**
     * @var string
     */
    #[ORM\Column(name: 'first_name', type: 'string')]
    private $first_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'last_name', type: 'string')]
    private $last_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email', type: 'string')]
    private $email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'address1', type: 'string')]
    private $address1;

    /**
     * @var string
     */
    #[ORM\Column(name: 'address2', nullable: true, type: 'string')]
    private $address2;

    /**
     * @var string
     */
    #[ORM\Column(name: 'state', type: 'string')]
    private $state;

    /**
     * @var string
     */
    #[ORM\Column(name: 'city', type: 'string')]
    private $city;

    /**
     * @var string
     */
    #[ORM\Column(name: 'post_code', type: 'string')]
    private $post_code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'country_iso_code', type: 'string')]
    private $country_iso_code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'second_email', nullable: true, type: 'string')]
    private $second_email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'third_email', nullable: true, type: 'string')]
    private $third_email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'gender', nullable: true, type: 'string')]
    private $gender;

    /**
     * @var string
     */
    #[ORM\Column(name: 'gender_specify', nullable: true, type: 'string')]
    private $gender_specify;

    /**
     * @var string
     */
    #[ORM\Column(name: 'statement_of_interest', nullable: true, type: 'string')]
    private $statement_of_interest;

    /**
     * @var string
     */
    #[ORM\Column(name: 'bio', nullable: true, type: 'string')]
    private $bio;

    /**
     * @var string
     */
    #[ORM\Column(name: 'irc', nullable: true, type: 'string')]
    private $irc;

    /**
     * @var string
     */
    #[ORM\Column(name: 'linked_in_profile', nullable: true, type: 'string')]
    private $linked_in_profile;

    /**
     * @var string
     */
    #[ORM\Column(name: 'twitter_name', nullable: true, type: 'string')]
    private $twitter_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'github_user', nullable: true, type: 'string')]
    private $github_user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'wechat_user', nullable: true, type: 'string')]
    private $wechat_user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'password', type: 'string')]
    private $password;

    /**
     * @var string
     */
    #[ORM\Column(name: 'password_salt', type: 'string')]
    private $password_salt;

    /**
     * @var string
     */
    #[ORM\Column(name: 'password_enc', type: 'string')]
    private $password_enc;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'email_verified', type: 'boolean')]
    private $email_verified;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email_verified_token_hash', nullable: true, type: 'string')]
    private $email_verified_token_hash;

    /**
     * @var \Datetime
     */
    #[ORM\Column(name: 'email_verified_date', nullable: true, type: 'datetime')]
    private $email_verified_date;
    /**
     * @var string
     */
    #[ORM\Column(name: 'language', nullable: true, type: 'string')]
    private $language;

    /**
     * @var \Datetime
     */
    #[ORM\Column(name: 'birthday', nullable: true, type: 'datetime')]
    private $birthday;

    /**
     * @var string
     */
    #[ORM\Column(name: 'spam_type', nullable: false, type: 'string')]
    private $spam_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'company', nullable: true, type: 'string')]
    private $company;

    /**
     * @var string
     */
    #[ORM\Column(name: 'job_title', nullable: true, type: 'string')]
    private $job_title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'phone_number', nullable: true, type: 'string')]
    private $phone_number;

    /**
     * @var string
     */
    #[ORM\Column(name: 'pic', type: 'string')]
    private $pic;

    /**
     * @var string
     */
    #[ORM\Column(name: 'external_id', type: 'string')]
    private $external_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'external_provider', type: 'string')]
    private $external_provider;

    /**
     * @var string
     */
    #[ORM\Column(name: 'external_pic', type: 'string')]
    private $external_pic;

    // relations
    /**
     * @var OAuth2OTP
     */
    #[ORM\JoinColumn(name: 'created_by_otp_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Models\OAuth2\OAuth2OTP::class, cascade: ['persist'])]
    private $created_by_otp;

    /**
     * @var UserRegistrationRequest
     */
    #[ORM\OneToOne(targetEntity: \App\libs\Auth\Models\UserRegistrationRequest::class, mappedBy: 'owner', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $registration_request;

    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Auth\User::class, cascade: ['persist'])]
    private $created_by;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: \Models\OAuth2\AccessToken::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $access_tokens;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: \Models\OAuth2\RefreshToken::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $refresh_tokens;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: \Models\OAuth2\Client::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $clients;

    /**
     * @var ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: \Models\OAuth2\Client::class, mappedBy: 'admin_users', fetch: 'EXTRA_LAZY')]
    private $managed_clients;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: \Auth\UserPasswordResetRequest::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $reset_password_requests;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: \Models\OpenId\OpenIdTrustedSite::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $trusted_sites;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: \Models\OAuth2\UserConsent::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $consents;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: \Models\UserAction::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $actions;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: \Auth\Affiliation::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $affiliations;

    /**
     * Many Users have Many Groups.
     */
    #[ORM\JoinTable(name: 'user_groups')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'group_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: \Auth\Group::class, inversedBy: 'users', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    private $groups;

    #[ORM\ManyToMany(targetEntity: \Models\OAuth2\ApiScopeGroup::class, mappedBy: 'users', fetch: 'EXTRA_LAZY')]
    private $scope_groups;

    public const SaltLen = 50;

    public function __construct()
    {
        parent::__construct();
        $this->active = true;
        $this->email_verified = false;
        // user profile settings
        $this->public_profile_show_photo = false;
        $this->public_profile_show_email = false;
        $this->public_profile_show_fullname = true;
        $this->public_profile_show_social_media_info = false;
        $this->public_profile_show_bio = true;
        $this->public_profile_show_telephone_number = false;
        $this->public_profile_allow_chat_with_me = false;

        $this->password = "";
        $this->identifier = null;
        $this->gender_specify = "";
        $this->password_enc = AuthHelper::AlgNative;
        $this->password_salt = AuthHelper::generateSalt(self::SaltLen, $this->password_enc);
        $this->login_failed_attempt = 0;
        $this->access_tokens = new ArrayCollection();
        $this->refresh_tokens = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->trusted_sites = new ArrayCollection();
        $this->consents = new ArrayCollection();
        $this->actions = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->affiliations = new ArrayCollection();
        $this->scope_groups = new ArrayCollection();
        $this->reset_password_requests = new ArrayCollection();
        $this->spam_type = self::SpamTypeNone;
        $this->company = null;
        $this->phone_number = null;
        $this->external_id = null;
        $this->external_provider = null;
        $this->external_pic = null;
        $this->created_by_otp = null;
    }

    /**
     * @param int $n
     * @return mixed
     */
    public function getLatestNActions(int $n = 10)
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['created_at' => 'desc'])->setMaxResults($n);
        return $this->actions->matching($criteria);
    }


    /**
     * Get the unique identifier for the user.
     * the one that is saved as session id on vendor/laravel/framework/src/Illuminate/Auth/Guard.php
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }

    /**
     * Get the password for the user.
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getIdentifier(): ?string
    {
        if(empty($this->identifier))
            return $this->email;

        return $this->identifier;
    }

    public function getEmail():?string
    {
        return PunnyCodeHelper::decodeEmail($this->email);
    }

    /**
     * @return string|null
     */
    public function getFullName(): ?string
    {
        $full_name = $this->getFirstName() . " " . $this->getLastName();
        return !empty(trim($full_name)) ? $full_name : $this->getEmail();
    }

    public function getFirstName():?string
    {
        return $this->first_name;
    }

    public function getLastName():?string
    {
        return $this->last_name;
    }

    public function getNickName(): ?string
    {
        return $this->getIdentifier();
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getCountry():?string
    {
        return $this->country_iso_code;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getDateOfBirth(): ?\DateTime
    {
        return $this->birthday;
    }

    /**
     * @return null|string
     */
    public function getDateOfBirthNice(): ?string
    {
        if (is_null($this->birthday)) return null;
        return $this->birthday->format("Y-m-d H:i:s");
    }


    public function getId():int
    {
        return (int)$this->id;
    }

    /**
     * @return bool
     */
    public function getShowProfileFullName():bool
    {
        return $this->public_profile_show_fullname > 0;
    }

    /**
     * @return bool
     */
    public function getShowProfilePic():bool
    {
        return $this->public_profile_show_photo > 0;
    }

    /**
     * @return bool
     */
    public function getShowProfileBio():bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getShowProfileEmail():bool
    {
        return $this->public_profile_show_email > 0;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    /**
     * @return Client[]
     */
    public function getAvailableClients(): array
    {
        $own_clients = $this->clients->filter(function (Client $client) {
            return !$client->hasResourceServer();
        })->toArray();

        $managed_clients = $this->managed_clients->filter(function (Client $client) {
            return !$client->hasResourceServer() && !$client->isOwner($this);
        })->toArray();

        return array_merge($own_clients, $managed_clients);
    }

    public function getManagedClients()
    {
        return $this->managed_clients->filter(function (Client $client) {
            return !$client->hasResourceServer() && !$client->isOwner($this);
        });
    }

    /**
     * Could use system scopes on registered clients
     * @return bool
     */
    public function canUseSystemScopes(): bool
    {
        if ($this->isSuperAdmin()) return true;
        return $this->belongToGroup(IOAuth2User::OAuth2SystemScopeAdminGroup);
    }

    /**
     * Is Server Administrator
     * @return bool
     */
    public function isOAuth2ServerAdmin(): bool
    {
        if ($this->isSuperAdmin()) return true;
        return $this->belongToGroup(IOAuth2User::OAuth2ServerAdminGroup);
    }

    /**
     * @return bool
     */
    public function isOpenIdServerAdmin(): bool
    {
        if ($this->isSuperAdmin()) return true;
        return $this->belongToGroup(IOpenIdUser::OpenIdServerAdminGroup);
    }

    /**
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->belongToGroup(IGroupSlugs::SuperAdminGroup);
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->belongToGroup(IGroupSlugs::SuperAdminGroup) || $this->belongToGroup(IGroupSlugs::AdminGroup);
    }

    /**
     * @param string $slug
     * @return bool
     */
    public function belongToGroup(string $slug): bool
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->eq('slug', $slug));
        return $this->groups->matching($criteria)->count() > 0;
    }

    /**
     * @param Group $group
     * @throws ValidationException
     */
    public function addToGroup(Group $group)
    {
        Log::debug
        (
            sprintf
            (
                "User::addToGroup user %s user current groups  %s group 2 add %s",
                $this->id,
                $this->getGroupsNice(),
                $group->getSlug()
            )
        );

        $current_user = Auth::user();
        if($current_user instanceof User){
            Log::debug
            (
                sprintf
                (
                    "User::addToGroup current user %s current user groups  %s user %s  user current groups  %s group 2 add %s",
                    $current_user->getId(),
                    $current_user->getGroupsNice(),
                    $this->id,
                    $this->getGroupsNice(),
                    $group->getSlug()
                )
            );

            if(!$current_user->isActive())
                throw new ValidationException("Current User is not active.");

            if(!$current_user->isSuperAdmin() && $group->getSlug() != IGroupSlugs::RawUsersGroup) {
                $current_user->deActivate();
                throw new ValidationException
                (
                    sprintf(
                        "Only Super Admins can add users to groups other than %s.",
                        IGroupSlugs::RawUsersGroup
                    )
                );
            }

            $action = sprintf
            (
                "ADD TO GROUP (%s) BY USER %s (%s)",

                $group->getName(),
                $current_user->getEmail(),
                $current_user->getId()
            );

            AddUserAction::dispatch($this->id, IPHelper::getUserIp(), $action);
        }

        if ($this->groups->contains($group))
            throw new ValidationException("User is already assigned to this group.");

        $this->groups->add($group);

        // slugs
        $monitored_security_groups = Config::get("audit.monitored_security_groups_set");
        Log::debug(sprintf("User::addToGroup monitored security groups %s", implode(',', $monitored_security_groups)));
        if(in_array($group->getSlug(), $monitored_security_groups)) {
            // trigger job
            Log::debug(sprintf("User::addToGroup dispatching NotifyMonitoredSecurityGroupActivity for user %s group %s", $this->id, $group->getSlug()));
            NotifyMonitoredSecurityGroupActivity::dispatch(
                NotifyMonitoredSecurityGroupActivity::ACTION_ADD_2_GROUP,
                $this->id,
                $this->getEmail(),
                $this->getFullName(),
                $group->getId(),
                $group->getName(),
                $group->getSlug()
            );
        }
    }

    /**
     * @param Group $group
     */
    public function removeFromGroup(Group $group)
    {
        Log::debug
        (
            sprintf
            (
                "User::removeFromGroup user %s  user current groups  %s group 2 remove %s",
                $this->id,
                $this->getGroupsNice(),
                $group->getSlug()
            )
        );
        $current_user = Auth::user();
        if($current_user instanceof User){
            Log::debug
            (
                sprintf
                (
                    "User::removeFromGroup current user %s current user groups  %s user %s  user current groups  %s group 2 remove %s",
                    $current_user->getId(),
                    $current_user->getGroupsNice(),
                    $this->id,
                    $this->getGroupsNice(),
                    $group->getSlug()
                )
            );

            if(!$current_user->isActive())
                throw new ValidationException("Current User is not active.");

            if(!$current_user->isSuperAdmin()) {
                $current_user->deActivate();
                throw new ValidationException
                (
                    "Only Super Admins can remove users from groups",
                );
            }

            $action = sprintf
            (
                "REMOVE FROM GROUP (%s) BY USER %s (%s)",

                $group->getName(),
                $current_user->getEmail(),
                $current_user->getId()
            );

            AddUserAction::dispatch($this->id, IPHelper::getUserIp(), $action);
        }

        if (!$this->groups->contains($group)) return;
        $this->groups->removeElement($group);
        // slugs
        $monitored_security_groups = Config::get("audit.monitored_security_groups_set");
        Log::debug(sprintf("User::removeFromGroup monitored security groups %s", implode(',', $monitored_security_groups)));
        if(in_array($group->getSlug(), $monitored_security_groups)) {
            // trigger job
            Log::debug(sprintf("User::removeFromGroup dispatching NotifyMonitoredSecurityGroupActivity for user %s group %s", $this->id, $group->getSlug()));
            NotifyMonitoredSecurityGroupActivity::dispatch(
                NotifyMonitoredSecurityGroupActivity::REMOVE_FROM_GROUP,
                $this->id,
                $this->getEmail(),
                $this->getFullName(),
                $group->getId(),
                $group->getName(),
                $group->getSlug()
            );
        }
    }

    public function getStreetAddress():?string
    {

        return $this->address1 . ' ' . $this->address2;
    }

    public function getRegion():?string
    {
        return $this->state;
    }

    public function getLocality():?string
    {
        return $this->city;
    }

    public function getPostalCode():?string
    {
        return $this->post_code;
    }

    public function getTrustedSites()
    {
        return $this->trusted_sites;
    }

    /**
     * @param OpenIdTrustedSite $site
     */
    public function addTrustedSite(OpenIdTrustedSite $site)
    {
        if ($this->trusted_sites->contains($site)) return;
        $this->trusted_sites->add($site);
        $site->setOwner($this);
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * @return int
     */
    public function getExternalIdentifier()
    {
        return $this->getAuthIdentifier();
    }

    /**
     * @return string
     */
    public function getFormattedAddress():?string
    {
        $street = $this->getStreetAddress();
        $region = $this->getRegion();
        $city = $this->getLocality();
        $zip_code = $this->getPostalCode();
        $country = $this->getCountry();

        $complete = $street;

        if (!empty($city))
            $complete .= ', ' . $city;

        if (!empty($region))
            $complete .= ', ' . $region;

        if (!empty($zip_code))
            $complete .= ', ' . $zip_code;

        if (!empty($country))
            $complete .= ', ' . $country;

        return $complete;
    }

    /**
     * @return mixed
     */
    public function getGroups()
    {
        return $this->groups;
    }

    public function getGroupsNice(): string
    {
        $groups = $this->getGroups();
        $res = [];
        foreach ($groups as $group) {
            $res[] = $group->getName();
        }
        return implode(', ', $res);
    }

    /**
     * @return ApiScope[]
     */
    public function getGroupScopes()
    {
        $scopes = [];
        $map = [];

        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->eq('active', true));
        $active_scope_groups = $this->scope_groups->matching($criteria);

        foreach ($active_scope_groups as $group) {
            foreach ($group->getScopes() as $scope) {
                if (!isset($map[$scope->getId()]))
                    $scopes[] = $scope;
            }
        }

        return $scopes;
    }

    /**
     * @param ApiScope $scope
     * @return bool
     * @throws ValidationException
     */
    public function isGroupScopeAllowed(ApiScope $scope): bool
    {
        if (!$scope->isAssignedByGroups()) throw new ValidationException("scope is not assigned by groups!");
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('active', true));
        $active_scope_groups = $this->scope_groups->matching($criteria);
        foreach ($active_scope_groups as $group) {
            if ($group->hasScope($scope)) return true;
        }
        return false;
    }

    public function clearEmailVerification(){
        $this->email_verified = false;
        $this->email_verified_date = null;
    }

    /**
     * @return bool
     */
    public function isEmailVerified()
    {
        return $this->email_verified;
    }

    public function clearTrustedSites(): void
    {
        $this->trusted_sites->clear();
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return "id";
    }

    /**
     * @return string
     */
    public function getPic(): string
    {
        $default_pic = Config::get("app.default_profile_image", null);
        try {
            $pic_key = sprintf("%s_user_pic", $this->id);
            $pic = Cache::get($pic_key);
            if(!empty($pic)) return $pic;

            if (!empty($this->pic)) {
                $storage = Storage::disk(Config::get("filesystems.cloud"));

                if(!is_null($storage)) {

                    $path = self::getProfilePicFolder();
                    $pic = null;

                    if($storage->exists(sprintf("%s/%s/%s", $path, $this->id, $this->pic))) {
                        Log::debug(sprintf("User::getPic Getting profile pic from %s/%s/%s", $path, $this->id, $this->pic));
                        $pic = $storage->url(sprintf("%s/%s/%s", $path, $this->id, $this->pic));
                    }

                    // legacy path format
                    if(empty($pic) && $storage->exists(sprintf("%s/%s", $path, $this->pic))) {
                        Log::debug(sprintf("User::getPic Getting profile pic from %s/%s", $path, $this->pic));
                        $pic = $storage->url(sprintf("%s/%s", $path, $this->pic));
                    }

                    if(!empty($pic)) {
                        Cache::forever($pic_key, $pic);
                        return $pic;
                    }
                }
            }

            if(!empty($this->external_pic)){
                return $this->external_pic;
            }

            if(!empty($default_pic))
                return $default_pic;

            return $this->getGravatarUrl();
        }
        catch(RequestException $ex1){
            Log::warning($ex1);
        }
        catch (\Exception $ex) {
            Log::warning($ex);
        }
        if(!empty($default_pic))
            return $default_pic;
        return $this->getGravatarUrl();
    }

    /**
     * @param string $pic
     */
    public function setPic(string $pic){
        $this->pic = $pic;
        $pic_key = sprintf("%s_user_pic", $this->id);
        Cache::forget($pic_key);
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     */
    private function getGravatarUrl(): string
    {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5($this->getEmail());
        return $url;
    }

    /**
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public function checkPassword(string $password): bool
    {
        if(empty($this->password))
        {
            Log::warning(sprintf("User %s (%s) has not password set.", $this->id, $this->getEmail()));
            return false;
        }

        if(empty($this->password_enc))
        {
            Log::warning(sprintf("User %s (%s) has not password encoding set.", $this->id, $this->getEmail()));
            return false;
        }

        return AuthHelper::check($password, $this->password, $this->password_enc, $this->password_salt);
    }

    public function canLogin(): bool
    {
        return $this->isEmailVerified() && $this->isActive();
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return $this
     */
    public function lock()
    {
        $this->deActivate();
        Event::dispatch(new UserLocked($this->getId()));
        $action = 'User Locked.';
        $current_user = Auth::user();
        if($current_user instanceof User) {
            $action = sprintf
            (
                "User Locked by user  %s (%s)",
                $current_user->getEmail(),
                $current_user->getId()
            );
        }
        AddUserAction::dispatch($this->getId(), IPHelper::getUserIp(), $action);
        return $this;
    }

    /**
     * @return $this
     */
    public function unlock()
    {
        $this->activate();

        $action = 'User Unlocked.';
        $current_user = Auth::user();
        if($current_user instanceof User) {
            $action = sprintf
            (
                "User Unlocked by user  %s (%s)",
                $current_user->getEmail(),
                $current_user->getId()
            );
        }
        AddUserAction::dispatch($this->getId(), IPHelper::getUserIp(), $action);
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublicProfileShowPhoto(): bool
    {
        return $this->public_profile_show_photo;
    }

    /**
     * @param bool $public_profile_show_photo
     */
    public function setPublicProfileShowPhoto(bool $public_profile_show_photo): void
    {
        $this->public_profile_show_photo = $public_profile_show_photo;
    }

    /**
     * @return bool
     */
    public function isPublicProfileShowFullname(): bool
    {
        return $this->public_profile_show_fullname;
    }

    /**
     * @param bool $public_profile_show_fullname
     */
    public function setPublicProfileShowFullname(bool $public_profile_show_fullname): void
    {
        $this->public_profile_show_fullname = $public_profile_show_fullname;
    }

    /**
     * @return bool
     */
    public function isPublicProfileShowEmail(): bool
    {
        return $this->public_profile_show_email;
    }

    /**
     * @param bool $public_profile_show_email
     */
    public function setPublicProfileShowEmail(bool $public_profile_show_email): void
    {
        $this->public_profile_show_email = $public_profile_show_email;
    }

    /**
     * @return bool
     */
    public function isPublicProfileAllowChatWithMe(): bool
    {
        return $this->public_profile_allow_chat_with_me;
    }

    /**
     * @param bool $public_profile_allow_chat_with_me
     */
    public function setPublicProfileAllowChatWithMe(bool $public_profile_allow_chat_with_me): void
    {
        $this->public_profile_allow_chat_with_me = $public_profile_allow_chat_with_me;
    }

    public function isPublicProfileShowSocialMediaInfo(): bool
    {
        return $this->public_profile_show_social_media_info;
    }

    public function setPublicProfileShowSocialMediaInfo(bool $public_profile_show_social_media_info): void
    {
        $this->public_profile_show_social_media_info = $public_profile_show_social_media_info;
    }

    public function isPublicProfileShowTelephoneNumber(): bool
    {
        return $this->public_profile_show_telephone_number;
    }

    public function setPublicProfileShowTelephoneNumber(bool $public_profile_show_telephone_number): void
    {
        $this->public_profile_show_telephone_number = $public_profile_show_telephone_number;
    }


    public function isPublicProfileShowBio(): bool
    {
        return $this->public_profile_show_bio;
    }

    public function setPublicProfileShowBio(bool $public_profile_show_bio): void
    {
        $this->public_profile_show_bio = $public_profile_show_bio;
    }


    /**
     * @return \DateTime|null
     */
    public function getLastLoginDate(): ?\DateTime
    {
        return $this->last_login_date;
    }

    /**
     * @param \DateTime $last_login_date
     */
    public function setLastLoginDate(\DateTime $last_login_date): void
    {
        $this->last_login_date = $last_login_date;
    }

    /**
     * @return int
     */
    public function getLoginFailedAttempt(): int
    {
        return $this->login_failed_attempt;
    }

    /**
     * @param int $login_failed_attempt
     */
    public function setLoginFailedAttempt(int $login_failed_attempt): void
    {
        $this->login_failed_attempt = $login_failed_attempt;
    }

    public function resetLoginFailedAttempts():void{
        $this->login_failed_attempt = 0;
    }

    /**
     * @return string
     */
    public function getAddress1(): ?string
    {
        return $this->address1;
    }

    /**
     * @param string $address1
     */
    public function setAddress1(string $address1): void
    {
        $this->address1 = $address1;
    }

    /**
     * @return string
     */
    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    /**
     * @param string $address2
     */
    public function setAddress2(string $address2): void
    {
        $this->address2 = $address2;
    }

    /**
     * @return string
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = TextUtils::trim($city);
    }

    /**
     * @return string
     */
    public function getPostCode(): ?string
    {
        return $this->post_code;
    }

    /**
     * @param string $post_code
     */
    public function setPostCode(string $post_code): void
    {
        $this->post_code = TextUtils::trim($post_code);
    }

    /**
     * @return string
     */
    public function getCountryIsoCode(): ?string
    {
        return $this->country_iso_code;
    }

    /**
     * @param string $country_iso_code
     */
    public function setCountryIsoCode(string $country_iso_code): void
    {
        $this->country_iso_code = $country_iso_code;
    }

    /**
     * @return string|null
     */
    public function getSecondEmail(): ?string
    {
        $res = PunnyCodeHelper::decodeEmail($this->second_email);
        Log::debug(sprintf("User::getSecondEmail res %s", $res));
        return $res;
    }

    /**
     * @param string $second_email
     */
    public function setSecondEmail(string $second_email): void
    {
        $this->second_email = PunnyCodeHelper::encodeEmail($second_email);
    }

    /**
     * @return string|null
     */
    public function getThirdEmail(): ?string
    {
        return PunnyCodeHelper::decodeEmail($this->third_email);
    }

    /**
     * @param string $third_email
     */
    public function setThirdEmail(string $third_email): void
    {
        $this->third_email = PunnyCodeHelper::encodeEmail($third_email);
    }

    /**
     * @return string|null
     */
    public function getStatementOfInterest(): ?string
    {
        return $this->statement_of_interest;
    }

    /**
     * @param string $statement_of_interest
     */
    public function setStatementOfInterest(string $statement_of_interest): void
    {
        $this->statement_of_interest = TextUtils::trim($statement_of_interest);
    }

    /**
     * @return string
     */
    public function getIrc(): ?string
    {
        return $this->irc;
    }

    /**
     * @param string $irc
     */
    public function setIrc(string $irc): void
    {
        $this->irc = TextUtils::trim($irc);
    }

    /**
     * @return string
     */
    public function getLinkedInProfile(): ?string
    {
        return $this->linked_in_profile;
    }

    /**
     * @param string $linked_in_profile
     */
    public function setLinkedInProfile(string $linked_in_profile): void
    {
        $this->linked_in_profile = $linked_in_profile;
    }

    /**
     * @return string
     */
    public function getGithubUser(): ?string
    {
        return $this->github_user;
    }

    /**
     * @param string $github_user
     */
    public function setGithubUser(string $github_user): void
    {
        $this->github_user = TextUtils::trim($github_user);
    }

    /**
     * @return string
     */
    public function getWechatUser(): ?string
    {
        return $this->wechat_user;
    }

    /**
     * @param string $wechat_user
     */
    public function setWechatUser(string $wechat_user): void
    {
        $this->wechat_user = TextUtils::trim($wechat_user);
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @throws ValidationException
     */
    public function setPassword(string $password): void
    {
        Log::debug(sprintf("User::setPassword %s (%s)", $this->email, $this->id));
        $password = TextUtils::trim($password);

        $min_length = Config::get("auth.password_min_length");
        if (strlen($password) < $min_length) {
            throw new ValidationException("Password must be at least $min_length characters.");
        }

        $max_length = Config::get("auth.password_max_length");
        if (strlen($password) > $max_length) {
            throw new ValidationException("Password must be at most $max_length characters.");
        }

        $pattern = Config::get("auth.password_shape_pattern");
        if (!preg_match("/$pattern/", $password)) {
            throw new ValidationException(Config::get("auth.password_shape_warning"));
        }

        if (empty($this->password_enc)) {
            $this->password_enc = AuthHelper::AlgNative;
        }
        $this->password_salt = AuthHelper::generateSalt(self::SaltLen, $this->password_enc);
        $this->password = AuthHelper::encrypt_password($password, $this->password_salt, $this->password_enc);

        $action = 'User set new password.';
        $current_user = Auth::user();
        if($current_user instanceof User) {
            $action = sprintf
            (
                "User set new password by user %s (%s)",
                $current_user->getEmail(),
                $current_user->getId()
            );
        }
        AddUserAction::dispatch($this->getId(), IPHelper::getUserIp(), $action, $this->email)->afterResponse();
    }

    /**
     * @return string
     */
    public function getPasswordEnc(): string
    {
        return $this->password_enc;
    }

    /**
     * @param string $password_enc
     */
    public function setPasswordEnc(string $password_enc): void
    {
        $this->password_enc = $password_enc;
    }

    /**
     * @return string
     */
    public function getEmailVerifiedTokenHash(): string
    {
        return $this->email_verified_token_hash;
    }

    /**
     * @param string $email_verified_token_hash
     */
    public function setEmailVerifiedTokenHash(string $email_verified_token_hash): void
    {
        $this->email_verified_token_hash = $email_verified_token_hash;
    }

    /**
     * @return \Datetime
     */
    public function getEmailVerifiedDate(): \Datetime
    {
        return $this->email_verified_date;
    }

    /**
     * @param \Datetime $email_verified_date
     */
    public function setEmailVerifiedDate(\Datetime $email_verified_date): void
    {
        $this->email_verified_date = $email_verified_date;
    }

    /**
     * @return \Datetime
     */
    public function getBirthday(): \Datetime
    {
        return $this->birthday;
    }

    /**
     * @param \Datetime $birthday
     */
    public function setBirthday(?\Datetime $birthday): void
    {
        $this->birthday = $birthday;
    }

    /**
     * @return ArrayCollection
     */
    public function getAccessTokens()
    {
        return $this->access_tokens;
    }

    /**
     * @param ArrayCollection $access_tokens
     */
    public function setAccessTokens(ArrayCollection $access_tokens): void
    {
        $this->access_tokens = $access_tokens;
    }

    /**
     * @return ArrayCollection
     */
    public function getRefreshTokens()
    {
        return $this->refresh_tokens;
    }

    public function getValidRefreshTokens()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('void', false));
        return $this->refresh_tokens->matching($criteria);
    }

    /**
     * @param ArrayCollection $refresh_tokens
     */
    public function setRefreshTokens(ArrayCollection $refresh_tokens): void
    {
        $this->refresh_tokens = $refresh_tokens;
    }

    /**
     * @return ArrayCollection
     */
    public function getConsents(): ArrayCollection
    {
        return $this->consents;
    }

    /**
     * @return ArrayCollection
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param UserAction $action
     */
    public function addUserAction(UserAction $action)
    {
        if ($this->actions->contains($action)) return;
        $this->actions->add($action);
        $action->setOwner($this);
    }

    /**
     * @return ArrayCollection
     */
    public function getAffiliations(): ArrayCollection
    {
        return $this->affiliations;
    }

    /**
     * @param ArrayCollection $affiliations
     */
    public function setAffiliations(ArrayCollection $affiliations): void
    {
        $this->affiliations = $affiliations;
    }

    /**
     * @param Client $client
     * @param string $scopes
     * @return UserConsent|null
     */
    public function findFirstConsentByClientAndScopes(Client $client, string $scopes): ?UserConsent
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->eq("client", $client));
        $consents = $this->consents->matching($criteria);
        if ($consents->count() == 0) return null;

        $scope_set = explode(' ', $scopes);
        sort($scope_set);

        $query = <<<SQL
SELECT uc  
FROM  Models\OAuth2\UserConsent uc
WHERE 
uc.owner = :user
AND uc.client = :client
AND uc.scopes LIKE :scopes
SQL;

        $query = $this->getEM()->createQuery($query);

        $query->setParameter("user", $this);
        $query->setParameter("client", $client);
        $query->setParameter("scopes", join(' ', $scope_set));

        $consent = $query->getOneOrNullResult();
        if (!is_null($consent)) return $consent;

        foreach ($consents as $consent) {
            $former_scope_set = explode(' ', $consent->getScope());
            // check if the requested scopes are included on the former consent present
            if (count(array_diff($scope_set, $former_scope_set)) == 0) {
                return $consent;
            }
        }
        return null;
    }

    /**
     * @param UserConsent $consent
     */
    public function addConsent(UserConsent $consent)
    {
        if ($this->consents->contains($consent)) return;
        $this->consents->add($consent);
        $consent->setOwner($this);
    }

    public function updateLastLoginDate(): void
    {
        $this->last_login_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return int
     */
    public function updateLoginFailedAttempt(): int
    {
        $this->login_failed_attempt = $this->login_failed_attempt + 1;
        $action = sprintf
        (
            "Login failed attempt (%s).",
            $this->login_failed_attempt
        );
        AddUserAction::dispatch($this->getId(), IPHelper::getUserIp(), $action);

        return $this->login_failed_attempt;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName(string $first_name): void
    {
        $this->first_name = TextUtils::trim($first_name);
    }

    /**
     * @param string $last_name
     */
    public function setLastName(string $last_name): void
    {
        $this->last_name = TextUtils::trim($last_name);
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $email = PunnyCodeHelper::encodeEmail($email);

        if (!empty($this->email) && $email != $this->email) {
            //we are setting a new email
            $this->clearResetPasswordRequests();
        }
        $this->email = $email;
    }

    /**
     * @param string $gender
     */
    public function setGender(string $gender): void
    {
        $this->gender = TextUtils::trim($gender);
    }

    /**
     * @param string $bio
     */
    public function setBio(string $bio): void
    {
        $this->bio = TextUtils::trim($bio);
    }

    public function activate():void {
        if(!$this->active) {
            $this->active = true;
            $this->spam_type = self::SpamTypeHam;
            // reset it
            $this->login_failed_attempt = 0;
            Event::dispatch(new UserSpamStateUpdated(
                    $this->getId()
                )
            );
        }
    }

    public function deActivate():void {
        if( $this->active) {
            $this->active = false;
            $this->spam_type = self::SpamTypeSpam;
            Event::dispatch(new UserSpamStateUpdated(
                    $this->getId()
                )
            );
        }
    }

    /**
     * @param bool $send_email_verified_notice
     * @return $this
     * @throws \Exception
     */
    public function verifyEmail(bool $send_email_verified_notice = true)
    {
        if (!$this->email_verified) {

            Log::debug(sprintf("User::verifyEmail verifying email %s", $this->getEmail()));
            $this->email_verified      = true;
            $this->spam_type           = self::SpamTypeHam;
            $this->active              = true;
            $this->lock                = false;
            $this->login_failed_attempt = 0;
            $this->email_verified_date = new \DateTime('now', new \DateTimeZone('UTC'));

            if($send_email_verified_notice)
                Event::dispatch(new UserEmailVerified($this->getId()));
            Event::dispatch(new UserSpamStateUpdated($this->getId()));
        }
        return $this;
    }

    /**
     * @return string
     * @throws ValidationException
     */
    public function generateEmailVerificationToken(): string
    {
        if($this->isEmailVerified()){
            throw new ValidationException(sprintf("User %s (%s) is already verified.", $this->id, $this->getEmail()));
        }

        $generator = new RandomGenerator();
        $token = strval($this->id) . $generator->randomToken();
        $this->email_verified_token_hash = self::createConfirmationTokenHash($token);
        return $token;
    }

    /**
     * @param string $token
     * @return string
     */
    public static function createConfirmationTokenHash(string $token): string
    {
        return md5($token);
    }

    /**
     * @param string $token
     * @return bool
     */
    public function checkConfirmationTokenHash(string $token): bool
    {
        return md5($token) == $this->email_verified_token_hash;
    }

    /**
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = TextUtils::trim($language);
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = strtolower(trim($identifier));
    }

    #[PostPersist]
    public function postPersist($args)
    {
        Event::dispatch(new UserCreated($this->getId()));
    }

    #[PreRemove]
    public function preRemove($args)
    {
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    #[PreUpdate]
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if($this->spam_type != self::SpamTypeNone ){
            if($args->hasChangedField("active")) return;
            $bio_changed = $args->hasChangedField("bio") && !empty($args->getNewValue('bio'));
            $email_changed = $args->hasChangedField("email");
            if( $bio_changed|| $email_changed) {
                // enqueue user for spam re checker
                Log::warning(sprintf("User::preUpdate user %s was marked for spam type reclasification.", $this->getEmail()));
                $this->resetSpamTypeClassification();
                Event::dispatch(new UserSpamStateUpdated($this->getId()));
            }
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name == "fullname")
            return $this->getFullName();

        if ($name == "pic")
            return $this->getPic();

        $res = $this->{$name};
        if ($name == "email" || $name == 'second_email' || $name == 'third_email')
            $res = PunnyCodeHelper::decodeEmail($res);

        if(is_string($res))
            Log::debug(sprintf("User::__get name %s res %s", $name, $res));

        return $res;
    }

    /**
     * @return string
     */
    public function getGenderSpecify(): ?string
    {
        return $this->gender_specify;
    }

    /**
     * @param string $gender_specify
     */
    public function setGenderSpecify(string $gender_specify): void
    {
        $this->gender_specify = $gender_specify;
    }

    /**
     * @param UserPasswordResetRequest $request
     */
    public function addPasswordResetRequest(UserPasswordResetRequest $request)
    {
        if ($this->reset_password_requests->contains($request)) return;
        $this->reset_password_requests->add($request);
    }

    /**
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    /**
     * @param User $created_by
     */
    public function setCreatedBy(User $created_by): void
    {
        $this->created_by = $created_by;
    }

    /**
     * @return bool
     */
    public function hasCreator(): bool
    {
        return $this->getCreatedById() > 0;
    }

    /**
     * @return int
     */
    public function getCreatedById(): int
    {
        try {
            return !is_null($this->created_by) ? $this->created_by->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return string|null
     */
    public function getTwitterName(): ?string
    {
        return $this->twitter_name;
    }

    /**
     * @param string $twitter_name
     */
    public function setTwitterName(string $twitter_name): void
    {
        $this->twitter_name = TextUtils::trim($twitter_name);
    }

    public function clearResetPasswordRequests(): void
    {
        $this->reset_password_requests->clear();
    }

    /**
     * @return string|null
     */
    public function getSpamType(): ?string
    {
        return $this->spam_type;
    }

    /**
     * @param string $spam_type
     * @throws ValidationException
     */
    public function setSpamType(string $spam_type): void
    {
        if(!in_array($spam_type, self::ValidSpamTypes))
            throw new ValidationException(sprintf("Not valid %s spam type value.", $spam_type));
        $this->spam_type = $spam_type;
    }


    public function resetSpamTypeClassification():void{
        $this->spam_type = self::SpamTypeNone;
    }

    /**
     * @return bool
     */
    public function isHam():bool{
        return $this->spam_type == self::SpamTypeHam;
    }

    /**
     * @return bool
     */
    public function isSpam():bool{
        return $this->spam_type == self::SpamTypeSpam;
    }

    /**
     * @return string
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany(string $company): void
    {
        $this->company = TextUtils::trim($company);
    }

    /**
     * @return string
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    /**
     * @param string $phone_number
     */
    public function setPhoneNumber(string $phone_number): void
    {
        $this->phone_number = TextUtils::trim($phone_number);
    }

    const ProfilePicFolder = 'profile_pics';

    public static function getProfilePicFolder():string{
        return self::ProfilePicFolder;
    }

    /**
     * @return string
     */
    public function getJobTitle(): ?string
    {
        return $this->job_title;
    }

    /**
     * @param string $job_title
     */
    public function setJobTitle(string $job_title): void
    {
        $this->job_title = TextUtils::trim($job_title);
    }

    /**
     * @return string
     */
    public function getExternalProvider(): ?string
    {
        return $this->external_provider;
    }

    /**
     * @param string $external_provider
     */
    public function setExternalProvider(string $external_provider): void
    {
        $this->external_provider = $external_provider;
    }

    /**
     * @return string
     */
    public function getExternalPic(): ?string
    {
        return $this->external_pic;
    }

    /**
     * @param string $external_pic
     */
    public function setExternalPic(string $external_pic): void
    {
        $this->external_pic = $external_pic;
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->external_id;
    }

    /**
     * @param string $external_id
     */
    public function setExternalId(string $external_id): void
    {
        $this->external_id = $external_id;
    }

    /**
     * @param string $full_name
     */
    public function setFullName(string $full_name):void{
        $full_name = TextUtils::trim($full_name);
        $name_parts = explode(" ", $full_name);
        if(count($name_parts) > 0)
            $this->first_name = $name_parts[0];
        if(count($name_parts) > 1)
            $this->last_name = $name_parts[1];
    }

    /**
     * @return bool
     */
    public function hasPasswordSet():bool{
        return !empty($this->password);
    }

    /**
     * @return bool
     */
    public function hasIdentifier():bool{
        return !empty($this->identifier);
    }

    /**
     * @return OAuth2OTP|null
     */
    public function getCreatedByOtp(): ?OAuth2OTP
    {
        return $this->created_by_otp;
    }

    /**
     * @param OAuth2OTP $created_by_otp
     */
    public function setCreatedByOtp(OAuth2OTP $created_by_otp): void
    {
        $this->created_by_otp = $created_by_otp;
    }

    /**
     * @return bool
     */
    public function createdByOTP():bool{
        return !is_null($this->created_by_otp);
    }

    #[ORM\PostUpdate] // :
    public function updated($args)
    {

    }

    private function formatFieldValue(string $field, $value):string{
        if($field === 'password') $value = "********";
        if($value instanceof \DateTime)
            $value = $value->format('Y-m-d H:i:s');
        if(empty($value)) $value = "EMPTY";
        return $value;
    }
    #[ORM\PreUpdate] // :
    public function updating(PreUpdateEventArgs $args)
    {
        $fields_2_check = [
            'identifier',
            'public_profile_show_photo',
            'public_profile_show_fullname',
            'public_profile_show_email',
            'public_profile_allow_chat_with_me',
            'public_profile_show_social_media_info',
            'public_profile_show_bio',
            'public_profile_show_telephone_number',
            'active',
            'first_name',
            'last_name',
            'email',
            'address1',
            'address2',
            'state',
            'city',
            'post_code',
            'country_iso_code',
            'second_email',
            'third_email',
            'gender',
            'gender_specify',
            'statement_of_interest',
            'bio',
            'irc',
            'linked_in_profile',
            'twitter_name',
            'github_user',
            'wechat_user',
            'password',
            'email_verified',
            'language',
            'birthday',
            'company',
            'job_title',
            'phone_number',
        ];
        $old_fields_changed = [];
        $new_fields_changed = [];

        foreach($fields_2_check as $field){
            if($args->hasChangedField($field)){
                $old_fields_changed[] = sprintf("%s: %s", $field, self::formatFieldValue($field, $args->getOldValue($field)));
                $new_fields_changed[] = sprintf("%s: %s", $field, self::formatFieldValue($field, $args->getNewValue($field)));
            }
        }

        if(count($old_fields_changed) == 0) return;
        if(count($new_fields_changed) == 0) return;

        $action = sprintf
        (
            "USER UPDATED from %s to %s", implode(", ", $old_fields_changed), implode(", ", $new_fields_changed)
        );

        AddUserAction::dispatch($this->id, IPHelper::getUserIp(), $action);
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

}