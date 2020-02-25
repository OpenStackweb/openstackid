<?php namespace Models\OpenId;
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
use OpenId\Models\IAssociation;
use DateTime;
use DateTimeZone;
use DateInterval;
use App\Models\Utils\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineOpenIdAssociationRepository")
 * @ORM\Table(name="openid_associations")
 * Class OpenIdAssociation
 * @package Models\OpenId
 */
class OpenIdAssociation extends BaseEntity implements IAssociation
{

    /**
     * @ORM\Column(name="identifier", type="string")
     * @var string
     */
    private $identifier;

    /**
     * @ORM\Column(name="mac_function", type="string")
     * @var string
     */
    private $mac_function;

    /**
     * @ORM\Column(name="secret", type="blob")
     * @var resource
     */
    private $secret;

    /**
     * @ORM\Column(name="realm", type="string")
     * @var string
     */
    private $realm;

    /**
     * @ORM\Column(name="type", type="integer")
     * @var int
     */
    private $type;

    /**
     * @ORM\Column(name="lifetime", type="integer")
     * @var int
     */
    private $lifetime;

    /**
     * @ORM\Column(name="issued", type="datetime")
     * @var DateTime
     */
    private $issued;

    /**
     * @return int
     * @throws \Exception
     */
    public function getRemainingLifetime():int
    {
        $void_date = clone $this->issued;
        $void_date->add(new DateInterval('PT' . intval($this->lifetime) . 'S'));
        $now        = new DateTime('now', new DateTimeZone("UTC"));
        //check validity...
        if ($now > $void_date)
            return -1;
        $seconds = abs($void_date->getTimestamp() - $now->getTimestamp());;
        return intval($seconds);
    }

    /**
     * @return string
     */
	public function getHandle():string
	{
		return $this->identifier;
	}

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getMacFunction(): string
    {
        return $this->mac_function;
    }

    /**
     * @param string $mac_function
     */
    public function setMacFunction(string $mac_function): void
    {
        $this->mac_function = $mac_function;
    }

    /**
     * @return string|null
     */
    public function getSecret(): ?string
    {
        if(is_string($this->secret))
            return $this->secret;
        if(is_resource($this->secret))
            return stream_get_contents($this->secret);
        return null;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return string|null
     */
    public function getRealm(): ?string
    {
        return $this->realm;
    }

    /**
     * @param string $realm
     */
    public function setRealm(string $realm): void
    {
        $this->realm = $realm;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
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

    /**
     * @return DateTime
     */
    public function getIssued(): DateTime
    {
        return $this->issued;
    }

    /**
     * @param DateTime $issued
     */
    public function setIssued(DateTime $issued): void
    {
        $this->issued = $issued;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function IsExpired(): bool
    {
        return $this->getRemainingLifetime() <= 0 ;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }
}