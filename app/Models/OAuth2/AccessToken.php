<?php namespace Models\OAuth2;
/**
 * Copyright 2015 OpenStack Foundation
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
use Auth\User;
use DateTime;
use DateInterval;
use DateTimeZone;
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineAccessTokenRepository")
 * @ORM\Table(name="oauth2_access_token")
 * @ORM\Cache("NONSTRICT_READ_WRITE")
 * Class AccessToken
 * @package Models\OAuth2
 */
class AccessToken extends BaseEntity {

    /**
     * @ORM\Column(name="from_ip", type="string")
     * @var string
     */
    private $from_ip;

    /**
     * @ORM\Column(name="value", type="string")
     * @var string
     */
    private $value;

    /**
     * @ORM\Column(name="associated_authorization_code", type="string", nullable=true)
     * @var string
     */
    private $associated_authorization_code;

    /**
     * @ORM\Column(name="lifetime", type="integer")
     * @var int
     */
    private $lifetime;

    /**
     * @ORM\Column(name="scope", type="string")
     * @var string
     */
    private $scope;

    /**
     * @ORM\Column(name="audience", type="string")
     * @var string
     */
    private $audience;

    /**
     * @ORM\ManyToOne(targetEntity="Models\OAuth2\RefreshToken", inversedBy="access_tokens", cascade={"persist"})
     * @ORM\JoinColumn(name="refresh_token_id", referencedColumnName="id", nullable=true)
     * @var RefreshToken
     */
    private $refresh_token;

    /**
     * @ORM\ManyToOne(targetEntity="Models\OAuth2\Client", inversedBy="access_tokens", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true)
     * @var Client
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\User", inversedBy="access_tokens", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * @var User
     */
    private $owner;

    private $friendly_scopes;


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return RefreshToken|null
     */
    public function getRefreshToken() : ?RefreshToken {
        return $this->refresh_token;
    }

    /**
     * @return Client
     */
    public function getClient():Client{
        return $this->client;
    }
    /**
     * @return User
     */
    public function getUser():User{
       return $this->owner;
    }


    public function getFriendlyScopes():string {
        return $this->friendly_scopes;
    }

    /**
     * @param string $friendly_scopes
     */
    public function setFriendlyScopes(string $friendly_scopes){
        $this->friendly_scopes = $friendly_scopes;
    }

    /**
     * @return int
     */
    public function getRemainingLifetime():int
    {
        //check is refresh token is stills alive... (ZERO is infinite lifetime)
        if (intval($this->lifetime) == 0) return 0;
        $created_at = clone $this->created_at;
        $created_at->setTimezone(new DateTimeZone('UTC'));
        $created_at->add(new DateInterval('PT' . intval($this->lifetime) . 'S'));
        $now = new DateTime(gmdate("Y-m-d H:i:s", time()), new DateTimeZone("UTC"));
        //check validity...
        if ($now > $created_at)
            return -1;
        $seconds = abs($created_at->getTimestamp() - $now->getTimestamp());;
        return $seconds;
    }

    /**
     * @return bool
     */
    public function isVoid():bool {
        //check lifetime...
        $created_at = $this->created_at;
        $created_at->add(new DateInterval('PT' . intval($this->lifetime) . 'S'));
        $now        = new DateTime(gmdate("Y-m-d H:i:s", time()), new DateTimeZone("UTC"));
        return ($now > $created_at);
    }

    /**
     * @return string
     */
    public function getFromIp(): string
    {
        return $this->from_ip;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string|null
     */
    public function getAssociatedAuthorizationCode(): ?string
    {
        return $this->associated_authorization_code;
    }

    /**
     * @return int
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @return string
     */
    public function getAudience(): string
    {
        return $this->audience;
    }

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @param string $from_ip
     */
    public function setFromIp(string $from_ip): void
    {
        $this->from_ip = $from_ip;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @param string $associated_authorization_code
     */
    public function setAssociatedAuthorizationCode(string $associated_authorization_code): void
    {
        $this->associated_authorization_code = $associated_authorization_code;
    }

    /**
     * @param int $lifetime
     */
    public function setLifetime(int $lifetime): void
    {
        $this->lifetime = $lifetime;
    }

    /**
     * @param string $scope
     */
    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * @param string $audience
     */
    public function setAudience(string $audience): void
    {
        $this->audience = $audience;
    }

    /**
     * @param RefreshToken $refresh_token
     */
    public function setRefreshToken(RefreshToken $refresh_token): void
    {
        $this->refresh_token = $refresh_token;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @param User $owner
     */
    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return int
     */
    public function getOwnerId():int{
        try {
            return is_null($this->owner) ? 0 : $this->owner->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasOwner():bool{
        return $this->getOwnerId() > 0;
    }

    /**
     * @return int
     */
    public function getClientId():int{
        try {
            return is_null($this->client) ? 0 : $this->client->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasClient():bool{
        return $this->getClientId() > 0;
    }

    /**
     * @return int
     */
    public function getRefreshTokenId():int{
        try {
            return is_null($this->refresh_token) ? 0 : $this->refresh_token->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasRefreshToken():bool{
        return $this->getRefreshTokenId() > 0;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }
} 