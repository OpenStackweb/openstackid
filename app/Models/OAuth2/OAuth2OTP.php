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

use App\libs\Utils\PunnyCodeHelper;
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping AS ORM;
use DateTime;
use DateInterval;
use DateTimeZone;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use OAuth2\OAuth2Protocol;
use OAuth2\Requests\OAuth2AccessTokenRequestPasswordless;
use Utils\IPHelper;
use Utils\Model\Identifier;
use Laminas\Math\Rand;

/**
 * @package Models\OAuth2
 */
#[ORM\Table(name: 'oauth2_otp')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineOAuth2OTPRepository::class)]
class OAuth2OTP extends BaseEntity implements Identifier
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'value', type: 'string')]
    private $value;

    /**
     * @var int
     */
    #[ORM\Column(name: 'length', type: 'integer')]
    private $length;

    /**
     * @var string
     */
    #[ORM\Column(name: '`connection`', type: 'string')]
    private $connection;

    /**
     * @var string
     */
    #[ORM\Column(name: 'send', type: 'string')]
    private $send;

    /**
     * @var string
     */
    #[ORM\Column(name: 'scope', type: 'string')]
    private $scope;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email', type: 'string')]
    private $email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'phone_number', type: 'string')]
    private $phone_number;

    /**
     * @var string
     */
    #[ORM\Column(name: 'nonce', type: 'string')]
    private $nonce;

    /**
     * @var int
     */
    #[ORM\Column(name: 'lifetime', type: 'integer')]
    private $lifetime;

    /**
     * @var string
     */
    #[ORM\Column(name: 'redirect_url', type: 'string')]
    private $redirect_url;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'redeemed_at', type: 'datetime')]
    private $redeemed_at;

    /**
     * @var string
     */
    #[ORM\Column(name: 'redeemed_from_ip', type: 'string')]
    private $redeemed_from_ip;

    /**
     * @var int
     */
    #[ORM\Column(name: 'redeemed_attempts', type: 'integer')]
    private $redeemed_attempts;

    /**
     * @var Client
     */
    #[ORM\JoinColumn(name: 'oauth2_client_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Models\OAuth2\Client::class, inversedBy: 'otp_grants', cascade: ['persist'])]
    private $client;

    /**
     * OAuth2OTP constructor.
     * @param int $length
     * @param int $lifetime
     */
    public function __construct(int $length, int $lifetime = 0 )
    {
        parent::__construct();
        $this->length = $length;
        $this->lifetime = $lifetime;
        $this->redeemed_at = null;
        $this->redeemed_attempts = 0;
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
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * @param string $connection
     */
    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @return string
     */
    public function getSend(): string
    {
        return $this->send;
    }

    /**
     * @param string $send
     */
    public function setSend(string $send): void
    {
        $this->send = $send;
    }

    /**
     * @return string
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     */
    public function setScope(?string $scope): void
    {
        $this->scope =  !empty($scope) ? trim($scope):null;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return PunnyCodeHelper::decodeEmail($this->email);
    }

    /**
     * @param string $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = PunnyCodeHelper::encodeEmail($email);
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
    public function setPhoneNumber(?string $phone_number): void
    {
        $this->phone_number =  !empty($phone_number) ? trim($phone_number):null;
    }

    /**
     * @return string
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * @param string $nonce
     */
    public function setNonce(?string $nonce): void
    {
        $this->nonce = !empty($nonce) ? trim($nonce): null;
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirect_url;
    }

    /**
     * @param string $redirect_url
     */
    public function setRedirectUrl(?string $redirect_url): void
    {
        $this->redirect_url = !empty($redirect_url) ? trim($redirect_url) :null;
    }

    /**
     * @return \DateTime|null
     */
    public function getRedeemedAt(): ?\DateTime
    {
        return $this->redeemed_at;
    }

    public function isRedeemed():bool{
        // inline OTP are always alive
        if ($this->connection === OAuth2Protocol::OAuth2PasswordlessConnectionInline) {
            return false;
        }
        return !is_null($this->redeemed_at);
    }

    /**
     * @throws ValidationException
     */
    public function redeem(): void
    {
        // inline OTP are always alive
        if ($this->connection === OAuth2Protocol::OAuth2PasswordlessConnectionInline) {
            return;
        }

        if (!is_null($this->redeemed_at))
            throw new ValidationException("OTP is already redeemed.");
        $this->redeemed_at = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->redeemed_from_ip = IPHelper::getUserIp();

        Log::debug(sprintf("OAuth2OTP::redeem from ip %s", $this->redeemed_from_ip));
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    public function hasClient():bool{
        return !is_null($this->client);
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @return int
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * @param int $lifetime
     */
    public function setLifetime(int $lifetime): void
    {
        $this->lifetime = $lifetime;
    }

    public function getRemainingLifetime()
    {
        //check is refresh token is stills alive... (ZERO is infinite lifetime)
        if (intval($this->lifetime) == 0) {
            return 0;
        }
        $created_at = clone $this->created_at;
        $created_at->add(new DateInterval('PT' . intval($this->lifetime) . 'S'));
        $now = new DateTime(gmdate("Y-m-d H:i:s", time()), new DateTimeZone("UTC"));
        //check validity...
        if ($now > $created_at) {
            return -1;
        }
        $seconds = abs($created_at->getTimestamp() - $now->getTimestamp());;

        return $seconds;
    }

    public function isAlive():bool
    {
        // inline OTP are always alive
        if ($this->connection === OAuth2Protocol::OAuth2PasswordlessConnectionInline) {
            return true;
        }
        return $this->getRemainingLifetime() >= 0;
    }

    public function clearClient():void{
        $this->client = null;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    const MaxRedeemAttempts = 3;

    public function logRedeemAttempt():void
    {
        if ($this->connection === OAuth2Protocol::OAuth2PasswordlessConnectionInline) {
            Log::debug(sprintf("OAuth2OTP::logRedeemAttempt trying to mark redeem attempt for %s and inline connection", $this->value));
            return;
        }
        Log::debug(sprintf("OAuth2OTP::logRedeemAttempt trying to mark redeem attempt for %s ", $this->value));
        if ($this->redeemed_attempts < self::MaxRedeemAttempts) {
            $this->redeemed_attempts = $this->redeemed_attempts + 1;
            Log::debug(sprintf("OAuth2OTP::logRedeemAttempt redeemed_attempts %s", $this->redeemed_attempts));
        }
    }

    public function isValid():bool
    {
        // inline OTP are always valid
        if ($this->connection === OAuth2Protocol::OAuth2PasswordlessConnectionInline) {
            return true;
        }
        return ($this->redeemed_attempts < self::MaxRedeemAttempts) && $this->isAlive();
    }

    public function getUserName():?string
    {
        return $this->connection == OAuth2Protocol::OAuth2PasswordlessEmail
        || $this->connection == OAuth2Protocol::OAuth2PasswordlessConnectionInline
            ? $this->getEmail() : $this->phone_number;
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function allowScope(string $scope):bool{
        $s1 = explode(" ", $scope);
        $s2 = explode(" ", $this->scope);
        return count(array_diff($s1, $s2)) == 0;
    }

    public function setValue(string $value)
    {
        $this->value = strtoupper($value);
    }

    public function getType(): string
    {
        return "otp";
    }

    const VsChar = "0123456789";

    public function generateValue(): string
    {
        // calculate value
        // entropy(SHANNON FANO Approx) len * log(count(VsChar))/log(2) = bits of entropy
        $this->value = Rand::getString($this->length, self::VsChar);
        return $this->value;
    }

    /**
     * @param OAuth2AccessTokenRequestPasswordless $request
     * @param int $length
     * @return OAuth2OTP
     */
    public static function fromRequest(OAuth2AccessTokenRequestPasswordless $request, int $length):OAuth2OTP{
        $instance = new self($length);
        $instance->connection = $request->getConnection();
        $instance->setEmail($request->getEmail());
        $instance->phone_number = $request->getPhoneNumber();
        $instance->scope = $request->getScopes();
        $instance->setValue($request->getOTP());
        $instance->redirect_url = $request->getRedirectUrl();
        return $instance;
    }

    /**
     * @param string $user_name
     * @param string $connection
     * @param string|null $value
     * @param string|null $scopes
     * @return OAuth2OTP|null
     */
    public static function fromParams(string $user_name, string $connection, ?string $value, string $scopes = null): ?OAuth2OTP
    {
        $instance = new self(strlen($value));
        $instance->connection = $connection;
        if ($connection == OAuth2Protocol::OAuth2PasswordlessConnectionEmail || $connection === OAuth2Protocol::OAuth2PasswordlessConnectionInline)
            $instance->setEmail($user_name);

        if ($connection == OAuth2Protocol::OAuth2PasswordlessConnectionSMS)
            $instance->phone_number = $user_name;
        $instance->setValue($value);
        if (!empty($scopes))
            $instance->setScope($scopes);

        return $instance;
    }

    // non db fields

    private $auth_time;

    private $user_id;

    /**
     * @param int $auth_time
     */
    public function setAuthTime(int $auth_time): void
    {
        $this->auth_time = $auth_time;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @return mixed
     */
    public function getAuthTime()
    {
        return $this->auth_time;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

}