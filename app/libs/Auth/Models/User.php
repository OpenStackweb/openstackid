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
use App\libs\Auth\Models\IGroupSlugs;
use App\libs\Auth\Models\UserRegistrationRequest;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use GuzzleHttp\Exception\RequestException;
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
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineUserRepository")
 * @ORM\Table(name="users")
 * @ORM\HasLifecycleCallbacks
 * Class User
 * @package Auth
 */
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
     * @ORM\Column(name="identifier", type="string")
     * @var string
     */
    private $identifier;

    /**
     * @ORM\Column(name="active", options={"default":0}, type="boolean")
     * @var bool
     */
    private $active;

    /**
     * @ORM\Column(name="public_profile_show_photo", options={"default":0}, type="boolean")
     * @var bool
     */
    private $public_profile_show_photo;

    /**
     * @ORM\Column(name="public_profile_show_fullname", options={"default":0}, type="boolean")
     * @var bool
     */
    private $public_profile_show_fullname;

    /**
     * @ORM\Column(name="public_profile_show_email", options={"default":0}, type="boolean")
     * @var bool
     */
    private $public_profile_show_email;

    /**
     * @ORM\Column(name="public_profile_allow_chat_with_me", options={"default":0}, type="boolean")
     * @var bool
     */
    private $public_profile_allow_chat_with_me;

    /**
     * @ORM\Column(name="last_login_date", type="datetime")
     * @var \DateTime
     */
    private $last_login_date;

    /**
     * @ORM\Column(name="login_failed_attempt", options={"unsigned":true, "default":0}, type="integer")
     * @var int
     */
    private $login_failed_attempt;

    /**
     * @ORM\Column(name="remember_token", nullable=true, type="string")
     * @var string
     */
    private $remember_token;

    /**
     * @ORM\Column(name="first_name", type="string")
     * @var string
     */
    private $first_name;

    /**
     * @ORM\Column(name="last_name", type="string")
     * @var string
     */
    private $last_name;

    /**
     * @ORM\Column(name="email", type="string")
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="address1", type="string")
     * @var string
     */
    private $address1;

    /**
     * @ORM\Column(name="address2", nullable=true, type="string")
     * @var string
     */
    private $address2;

    /**
     * @ORM\Column(name="state", type="string")
     * @var string
     */
    private $state;

    /**
     * @ORM\Column(name="city", type="string")
     * @var string
     */
    private $city;

    /**
     * @ORM\Column(name="post_code", type="string")
     * @var string
     */
    private $post_code;

    /**
     * @ORM\Column(name="country_iso_code", type="string")
     * @var string
     */
    private $country_iso_code;

    /**
     * @ORM\Column(name="second_email", nullable=true, type="string")
     * @var string
     */
    private $second_email;

    /**
     * @ORM\Column(name="third_email", nullable=true, type="string")
     * @var string
     */
    private $third_email;

    /**
     * @ORM\Column(name="gender" , nullable=true,type="string")
     * @var string
     */
    private $gender;

    /**
     * @ORM\Column(name="gender_specify" , nullable=true,type="string")
     * @var string
     */
    private $gender_specify;

    /**
     * @ORM\Column(name="statement_of_interest",  nullable=true, type="string")
     * @var string
     */
    private $statement_of_interest;

    /**
     * @ORM\Column(name="bio", nullable=true, type="string")
     * @var string
     */
    private $bio;

    /**
     * @ORM\Column(name="irc", nullable=true, type="string")
     * @var string
     */
    private $irc;

    /**
     * @ORM\Column(name="linked_in_profile", nullable=true,type="string")
     * @var string
     */
    private $linked_in_profile;

    /**
     * @ORM\Column(name="twitter_name", nullable=true,type="string")
     * @var string
     */
    private $twitter_name;

    /**
     * @ORM\Column(name="github_user", nullable=true,type="string")
     * @var string
     */
    private $github_user;

    /**
     * @ORM\Column(name="wechat_user", nullable=true, type="string")
     * @var string
     */
    private $wechat_user;

    /**
     * @ORM\Column(name="password", type="string")
     * @var string
     */
    private $password;

    /**
     * @ORM\Column(name="password_salt", type="string")
     * @var string
     */
    private $password_salt;

    /**
     * @ORM\Column(name="password_enc", type="string")
     * @var string
     */
    private $password_enc;

    /**
     * @ORM\Column(name="email_verified", type="boolean")
     * @var boolean
     */
    private $email_verified;

    /**
     * @ORM\Column(name="email_verified_token_hash", nullable=true, type="string")
     * @var string
     */
    private $email_verified_token_hash;

    /**
     * @ORM\Column(name="email_verified_date",  nullable=true, type="datetime")
     * @var \Datetime
     */
    private $email_verified_date;
    /**
     * @ORM\Column(name="language", nullable=true, type="string")
     * @var string
     */
    private $language;

    /**
     * @ORM\Column(name="birthday",  nullable=true, type="datetime")
     * @var \Datetime
     */
    private $birthday;

    /**
     * @ORM\Column(name="spam_type", nullable=false, type="string")
     * @var string
     */
    private $spam_type;

    /**
     * @ORM\Column(name="company", nullable=true, type="string")
     * @var string
     */
    private $company;

    /**
     * @ORM\Column(name="job_title", nullable=true, type="string")
     * @var string
     */
    private $job_title;

    /**
     * @ORM\Column(name="phone_number", nullable=true, type="string")
     * @var string
     */
    private $phone_number;

    /**
     * @ORM\Column(name="pic", type="string")
     * @var string
     */
    private $pic;

    /**
     * @ORM\Column(name="external_id", type="string")
     * @var string
     */
    private $external_id;

    /**
     * @ORM\Column(name="external_provider", type="string")
     * @var string
     */
    private $external_provider;

    /**
     * @ORM\Column(name="external_pic", type="string")
     * @var string
     */
    private $external_pic;

    // relations


    /**
     * @ORM\ManyToOne(targetEntity="Models\OAuth2\OAuth2OTP", cascade={"persist"})
     * @ORM\JoinColumn(name="created_by_otp_id", referencedColumnName="id", nullable=true)
     * @var OAuth2OTP
     */
    private $created_by_otp;

    /**
     * @ORM\OneToOne(targetEntity="App\libs\Auth\Models\UserRegistrationRequest", mappedBy="owner", cascade={"persist","remove"}, orphanRemoval=true)
     * @var UserRegistrationRequest
     */
    private $registration_request;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\User", cascade={"persist"})
     * @ORM\JoinColumn(name="created_by_id", referencedColumnName="id", nullable=true)
     * @var User
     */
    private $created_by;

    /**
     * @ORM\OneToMany(targetEntity="Models\OAuth2\AccessToken", mappedBy="owner", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $access_tokens;

    /**
     * @ORM\OneToMany(targetEntity="Models\OAuth2\RefreshToken", mappedBy="owner", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $refresh_tokens;

    /**
     * @ORM\OneToMany(targetEntity="Models\OAuth2\Client", mappedBy="user", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $clients;

    /**
     * @ORM\ManyToMany(targetEntity="Models\OAuth2\Client", mappedBy="admin_users", fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $managed_clients;

    /**
     * @ORM\OneToMany(targetEntity="Auth\UserPasswordResetRequest", mappedBy="owner", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $reset_password_requests;

    /**
     * @ORM\OneToMany(targetEntity="Models\OpenId\OpenIdTrustedSite", mappedBy="owner", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $trusted_sites;

    /**
     * @ORM\OneToMany(targetEntity="Models\OAuth2\UserConsent", mappedBy="owner", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $consents;

    /**
     * @ORM\OneToMany(targetEntity="Models\UserAction", mappedBy="owner", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $actions;

    /**
     * @ORM\OneToMany(targetEntity="Auth\Affiliation", mappedBy="owner", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $affiliations;

    /**
     * Many Users have Many Groups.
     * @ORM\ManyToMany(targetEntity="Auth\Group", inversedBy="users", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="user_groups",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")})
     */
    private $groups;

    /**
     * @ORM\ManyToMany(targetEntity="Models\OAuth2\ApiScopeGroup", mappedBy="users", fetch="EXTRA_LAZY")
     */
    private $scope_groups;

    public const SaltLen = 50;

    public function __construct()
    {
        parent::__construct();
        $this->active = true;
        $this->email_verified = false;
        $this->public_profile_show_photo = false;
        $this->public_profile_show_email = false;
        $this->public_profile_show_fullname = false;
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
        $this->public_profile_allow_chat_with_me = false;
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
        return $this->identifier;
    }

    public function getEmail():string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getFullName(): ?string
    {
        $full_name = $this->getFirstName() . " " . $this->getLastName();
        return !empty(trim($full_name)) ? $full_name : $this->email;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function getLastName()
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

    public function getCountry()
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


    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @return bool
     */
    public function getShowProfileFullName()
    {
        return $this->public_profile_show_fullname > 0;
    }

    /**
     * @return bool
     */
    public function getShowProfilePic()
    {
        return $this->public_profile_show_photo > 0;
    }

    /**
     * @return bool
     */
    public function getShowProfileBio()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getShowProfileEmail()
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
     */
    public function addToGroup(Group $group)
    {
        if ($this->groups->contains($group)) return;
        $this->groups->add($group);
    }

    /**
     * @param Group $group
     */
    public function removeFromGroup(Group $group)
    {
        if (!$this->groups->contains($group)) return;
        $this->groups->removeElement($group);
    }

    public function clearGroups(): void
    {
        $this->groups->clear();
    }

    public function getStreetAddress()
    {

        return $this->address1 . ' ' . $this->address2;
    }

    public function getRegion()
    {
        return $this->state;
    }

    public function getLocality()
    {
        return $this->city;
    }

    public function getPostalCode()
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
    public function getFormattedAddress()
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
                $storage = Storage::disk('swift');
                if(!is_null($storage)) {
                    $pic = $storage->url(sprintf("%s/%s", self::getProfilePicFolder(), $this->pic));
                    Cache::forever($pic_key, $pic);
                    return $pic;
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
        $url .= md5(strtolower(trim($this->email)));
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
            Log::warning(sprintf("User %s (%s) has not password set.", $this->id, $this->email));
            return false;
        }

        if(empty($this->password_enc))
        {
            Log::warning(sprintf("User %s (%s) has not password encoding set.", $this->id, $this->email));
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
        return $this;
    }

    /**
     * @return $this
     */
    public function unlock()
    {
        $this->activate();
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
        $this->city = $city;
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
        $this->post_code = $post_code;
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
        return $this->second_email;
    }

    /**
     * @param string $second_email
     */
    public function setSecondEmail(string $second_email): void
    {
        $this->second_email = $second_email;
    }

    /**
     * @return string|null
     */
    public function getThirdEmail(): ?string
    {
        return $this->third_email;
    }

    /**
     * @param string $third_email
     */
    public function setThirdEmail(string $third_email): void
    {
        $this->third_email = $third_email;
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
        $this->statement_of_interest = $statement_of_interest;
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
        $this->irc = $irc;
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
        $this->github_user = $github_user;
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
        $this->wechat_user = $wechat_user;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $password = trim($password);

        if(empty($this->password_enc)){
            $this->password_enc = AuthHelper::AlgNative;
        }
        $this->password_salt = AuthHelper::generateSalt(self::SaltLen, $this->password_enc);
        $this->password = AuthHelper::encrypt_password($password, $this->password_salt, $this->password_enc);
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
    public function getAccessTokens(): ArrayCollection
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
    public function getRefreshTokens(): ArrayCollection
    {
        return $this->refresh_tokens;
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
    public function getActions(): ArrayCollection
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
        return $this->login_failed_attempt;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName(string $first_name): void
    {
        $this->first_name = $first_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName(string $last_name): void
    {
        $this->last_name = $last_name;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $email = trim($email);
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
        $this->gender = $gender;
    }

    /**
     * @param string $bio
     */
    public function setBio(string $bio): void
    {
        $this->bio = $bio;
    }

    public function activate():void {
        if(!$this->active) {
            $this->active = true;
            $this->spam_type = self::SpamTypeHam;
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

            Log::debug(sprintf("User::verifyEmail verifying email %s", $this->email));
            $this->email_verified      = true;
            $this->spam_type           = self::SpamTypeHam;
            $this->active              = true;
            $this->lock                = false;
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
            throw new ValidationException(sprintf("User %s (%s) is already verified.", $this->id, $this->email));
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
        $this->language = $language;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @ORM\postPersist
     */
    public function postPersist($args)
    {
        Event::dispatch(new UserCreated($this->getId()));
    }

    /**
     * @ORM\preRemove
     */
    public function preRemove($args)
    {
    }

    /**
     * @ORM\preUpdate
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if($this->spam_type != self::SpamTypeNone ){
            if($args->hasChangedField("active")) return;
            $bio_changed = $args->hasChangedField("bio") && !empty($args->getNewValue('bio'));
            $email_changed = $args->hasChangedField("email");
            if( $bio_changed|| $email_changed) {
                // enqueue user for spam re checker
                Log::warning(sprintf("User::preUpdate user %s was marked for spam type reclasification.", $this->email));
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
        $this->twitter_name = $twitter_name;
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
        $this->company = $company;
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
        $this->phone_number = $phone_number;
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
        $this->job_title = $job_title;
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

}