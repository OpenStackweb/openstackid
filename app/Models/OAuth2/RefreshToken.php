<?php namespace Models\OAuth2;
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
use Auth\User;
use DateInterval;
use DateTime;
use DateTimeZone;
use App\Models\Utils\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineRefreshTokenRepository")
 * @ORM\Table(name="oauth2_refresh_token")
 * @ORM\Cache("NONSTRICT_READ_WRITE")
 * Class RefreshToken
 * Refresh Token Entity
 */
class RefreshToken extends BaseEntity {

    /**
     * @ORM\Column(name="value", type="string")
     * @var string
     */
    private $value;

    /**
     * @ORM\Column(name="from_ip", type="string")
     * @var string
     */
    private $from_ip;

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
     * @ORM\Column(name="void", type="boolean")
     * @var bool
     */
    private $void;

    /**
     * @var
     */
    private $friendly_scopes;

    /**
     * @ORM\OneToMany(targetEntity="Models\OAuth2\AccessToken", mappedBy="refresh_token", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     *
     */
    private $access_tokens;

    /**
     * @ORM\ManyToOne(targetEntity="Models\OAuth2\Client", inversedBy="refresh_tokens", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true)
     * @var Client
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\User", inversedBy="refresh_tokens", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * @var User
     */
    private $owner;

    public function __construct()
    {
        parent::__construct();
        $this->void = false;
        $this->access_tokens = new ArrayCollection();
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

    /**
     * @return bool
     * @throws \Exception
     */
    public function isVoid():bool {
        if($this->void) return true;
        if(intval($this->lifetime) == 0) return false;
        //check lifetime...
        $created_at = clone $this->created_at;
        $created_at->add(new DateInterval('PT' . intval($this->lifetime) . 'S'));
        $now        = new DateTime(gmdate("Y-m-d H:i:s", time()), new DateTimeZone("UTC"));
        return ($now > $created_at);
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getRemainingLifetime():int
    {
        //check is refresh token is stills alive... (ZERO is infinite lifetime)
        if (intval($this->lifetime) == 0) return 0;
        $created_at = clone $this->created_at;
        $created_at->add(new DateInterval('PT' . intval($this->lifetime) . 'S'));
        $now = new DateTime(gmdate("Y-m-d H:i:s", time()), new DateTimeZone("UTC"));
        //check validity...
        if ($now > $created_at)
            return -1;
        $seconds = abs($created_at->getTimestamp() - $now->getTimestamp());;
        return $seconds;
    }

    public function getFriendlyScopes(){
        return $this->friendly_scopes;
    }

    public function setFriendlyScopes($friendly_scopes){
        $this->friendly_scopes = $friendly_scopes;
    }

    public function setVoid(){
        $this->void = true;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getFromIp(): string
    {
        return $this->from_ip;
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
     * @return ArrayCollection
     */
    public function getAccessTokens(): ArrayCollection
    {
        return $this->access_tokens;
    }

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @param string $from_ip
     */
    public function setFromIp(string $from_ip): void
    {
        $this->from_ip = $from_ip;
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
     * @param ArrayCollection $access_tokens
     */
    public function setAccessTokens(ArrayCollection $access_tokens): void
    {
        $this->access_tokens = $access_tokens;
    }

    public function addAccessToken(AccessToken $accessToken){
        if($this->access_tokens->contains($accessToken)) return;
        $this->access_tokens->add($accessToken);
        $accessToken->setRefreshToken($this);
    }

    public function removeAccessToken(AccessToken $accessToken){
        if(!$this->access_tokens->contains($accessToken)) return;
        $this->access_tokens->removeElement($accessToken);
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
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }

}