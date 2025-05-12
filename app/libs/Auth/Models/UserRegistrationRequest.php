<?php namespace App\libs\Auth\Models;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\libs\Utils\TextUtils;
use App\Models\Utils\BaseEntity;
use Auth\User;
use Doctrine\ORM\Mapping AS ORM;
use Models\OAuth2\Client;
/**
 * @package App\libs\Auth\Models
 */
#[ORM\Table(name: 'user_registration_requests')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineUserRegistrationRequestRepository::class)]
class UserRegistrationRequest extends BaseEntity
{
    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\OneToOne(targetEntity: \Auth\User::class, inversedBy: 'registration_request')]
    private $owner;

    /**
     * @var Client
     */
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Models\OAuth2\Client::class, cascade: ['persist'])]
    private $client;

    /**
     * @var string
     */
    #[ORM\Column(name: 'hash', type: 'string')]
    private $hash;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email', type: 'string')]
    private $email;

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
    #[ORM\Column(name: 'company', type: 'string')]
    private $company;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'redeem_at', nullable: true, type: 'datetime')]
    private $redeem_at;

    /**
     * @var string
     */
    #[ORM\Column(name: 'country_iso_code', type: 'string')]
    private $country_iso_code;

    public function __construct()
    {
        parent::__construct();
        $this->email = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->company = '';
        $this->country_iso_code = '';
    }

    /**
     * @return User
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     */
    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return PunnyCodeHelper::decodeEmail($this->email);
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = PunnyCodeHelper::encodeEmail($email);
    }

    /**
     * @return string
     */
    public function getFirstName():?string
    {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName(string $first_name): void
    {
        $this->first_name = TextUtils::trim($first_name);
    }

    /**
     * @return string
     */
    public function getLastName():?string
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName(string $last_name): void
    {
        $this->last_name = TextUtils::trim($last_name);
    }


    public function getCompany():?string{
        return $this->company;
    }

    public function setCompany(string $company):void{
        $this->company = TextUtils::trim($company);
    }

    /**
     * @return \DateTime|null
     */
    public function getRedeemAt(): ?\DateTime
    {
        return $this->redeem_at;
    }

    /**
     * @param \DateTime $redeem_at
     */
    public function setRedeemAt(\DateTime $redeem_at): void
    {
        $this->redeem_at = $redeem_at;
    }

    /**
     * @return string
     */
    public function getCountryIsoCode():?string
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
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function redeem():void{
        $this->redeem_at = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return bool
     */
    public function isRedeem():bool {
        return !is_null($this->redeem_at);
    }

}