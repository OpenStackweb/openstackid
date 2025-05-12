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

use App\libs\Utils\DeviceInfoHelper;
use Auth\User;
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping AS ORM;
use Illuminate\Support\Facades\Log;

/**
 * @package Models\OAuth2
 */
#[ORM\Table(name: 'oauth2_access_token')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineAccessTokenRepository::class)]
#[ORM\Cache('NONSTRICT_READ_WRITE')] // Class AccessToken
class AccessToken extends BaseEntity {

    /**
     * @var string
     */
    #[ORM\Column(name: 'from_ip', type: 'string')]
    private $from_ip;

    /**
     * @var string
     */
    #[ORM\Column(name: 'value', type: 'string')]
    private $value;

    /**
     * @var string
     */
    #[ORM\Column(name: 'associated_authorization_code', type: 'string', nullable: true)]
    private $associated_authorization_code;

    /**
     * @var int
     */
    #[ORM\Column(name: 'lifetime', type: 'integer')]
    private $lifetime;

    /**
     * @var string
     */
    #[ORM\Column(name: 'scope', type: 'string')]
    private $scope;

    /**
     * @var string
     */
    #[ORM\Column(name: 'audience', type: 'string')]
    private $audience;

    /**
     * @var RefreshToken
     */
    #[ORM\JoinColumn(name: 'refresh_token_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Models\OAuth2\RefreshToken::class, inversedBy: 'access_tokens', cascade: ['persist'])]
    private $refresh_token;

    /**
     * @var Client
     */
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Models\OAuth2\Client::class, inversedBy: 'access_tokens', cascade: ['persist'])]
    private $client;

    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Auth\User::class, inversedBy: 'access_tokens', cascade: ['persist'])]
    private $owner;

    private $friendly_scopes;

    /**
     * @var string
     */
    #[ORM\Column(name: 'device_info', type: 'string')]
    private $device_info;


    public function __construct()
    {
        parent::__construct();
        $this->device_info = DeviceInfoHelper::getDeviceInfo();
    }

    public function getDeviceInfo():?string{
        return $this->device_info;
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
        if ($this->lifetime == 0) return 0;
        $created_at = clone $this->created_at;
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $time_elapsed = abs($now->getTimestamp() - $created_at->getTimestamp());
        Log::debug(sprintf("AccessToken::getRemainingLifetime id %s time_elapsed %s", $this->id, $time_elapsed));
        return $this->lifetime > $time_elapsed ? $this->lifetime - $time_elapsed : -1;
    }

    /**
     * @return bool
     */
    public function isVoid():bool {
        $remainingLifeTime =  $this->getRemainingLifetime() ;
        Log::debug(sprintf("AccessToken::isVoid id %s remainingLifeTime %s", $this->id, $remainingLifeTime));
        return $remainingLifeTime == -1;
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