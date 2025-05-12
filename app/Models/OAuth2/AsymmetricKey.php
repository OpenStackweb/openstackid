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
use App\Models\Utils\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use OAuth2\Models\IAsymmetricKey;
use jwa\cryptographic_algorithms\ICryptoAlgorithm;
use jwa\cryptographic_algorithms\KeyManagementAlgorithms_Registry;
use jwa\cryptographic_algorithms\DigitalSignatures_MACs_Registry;
use DateTime;
use Exception;
/**
 * @package Models\OAuth2
 */
#[ORM\Table(name: 'oauth2_asymmetric_keys')]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'class_name', type: 'string')]
#[ORM\DiscriminatorMap(['ClientPublicKey' => 'ClientPublicKey', 'ServerPrivateKey' => 'ServerPrivateKey'])] // Class AsymmetricKey
abstract class AsymmetricKey extends BaseEntity implements IAsymmetricKey
{

    /**
     * @var string
     */
    #[ORM\Column(name: 'kid', type: 'string')]
    protected $kid;

    /**
     * @var string
     */
    #[ORM\Column(name: 'pem_content', type: 'string')]
    protected $pem_content;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'active', type: 'boolean')]
    protected $active;

    /**
     * @var string
     */
    #[ORM\Column(name: '`usage`', type: 'string')]
    protected $usage;

    /**
     * @var string
     */
    #[ORM\Column(name: '`type`', type: 'string')]
    protected $type;

    /**
     * @var string
     */
    #[ORM\Column(name: '`alg`', type: 'string')]
    protected $alg;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'last_use', type: 'datetime')]
    protected $last_use;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'valid_from', type: 'datetime')]
    protected $valid_from;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'valid_to', type: 'datetime')]
    protected $valid_to;

    /**
     * @return string
     */
    public function getType():string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getUse():string
    {
        return $this->usage;
    }

    /**
     * @return bool
     */
    public function isActive():bool
    {
        return (bool)$this->active;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastUse():?DateTime
    {
        return $this->last_use;
    }

    /**
     * @return $this
     */
    public function markAsUsed()
    {
        $this->last_use = new DateTime('now', new \DateTimeZone('UTC'));
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyId()
    {
        return $this->kid;
    }

    private function calculateThumbprint($alg)
    {
        $res = '';
        try {
            $pem = str_replace(["\n", "\r"], '', trim($this->getPublicKeyPEM()));
            $res = strtoupper(hash($alg, base64_decode($pem)));
        }
        catch(Exception $ex)
        {
            $res = 'INVALID';
        }
        return $res;
    }

    /**
     * @return string
     */
    public function getSHA_1_Thumbprint()
    {
        return $this->calculateThumbprint('sha1');
    }

    /**
     * @return string
     */
    public function getSHA_256_Thumbprint()
    {
        return $this->calculateThumbprint('sha256');
    }

    abstract public function getPublicKeyPEM();

    /**
     * @return string
     */
    public function getPEM()
    {
        return $this->pem_content;
    }

    /**
     * checks validity range with now
     * @return bool
     */
    public function isExpired()
    {
        $now = new DateTime();
        return ( $this->valid_from <= $now && $this->valid_to >= $now);
    }


    /**
     * algorithm intended for use with the key
     * @return ICryptoAlgorithm
     */
    public function getAlg()
    {
        $algorithm = DigitalSignatures_MACs_Registry::getInstance()->get($this->alg);

        if(is_null($algorithm))
        {
            $algorithm = KeyManagementAlgorithms_Registry::getInstance()->get($this->alg);
        }
        return $algorithm;
    }

    public function getAlgName():string{
        return $this->alg;
    }

    /**
     * @return string
     */
    public function getKid(): string
    {
        return $this->kid;
    }

    /**
     * @param string $kid
     */
    public function setKid(string $kid): void
    {
        $this->kid = $kid;
    }

    /**
     * @return string
     */
    public function getPemContent(): string
    {
        return $this->pem_content;
    }

    /**
     * @param string $pem_content
     */
    public function setPemContent(string $pem_content): void
    {
        $this->pem_content = $pem_content;
    }

    /**
     * @return string
     */
    public function getUsage(): string
    {
        return $this->usage;
    }

    /**
     * @param string $usage
     */
    public function setUsage(string $usage): void
    {
        $this->usage = $usage;
    }

    /**
     * @return DateTime
     */
    public function getValidFrom(): DateTime
    {
        return $this->valid_from;
    }

    /**
     * @param DateTime $valid_from
     */
    public function setValidFrom(DateTime $valid_from): void
    {
        $this->valid_from = $valid_from;
    }

    /**
     * @return DateTime
     */
    public function getValidTo(): DateTime
    {
        return $this->valid_to;
    }

    /**
     * @param DateTime $valid_to
     */
    public function setValidTo(DateTime $valid_to): void
    {
        $this->valid_to = $valid_to;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param string $alg
     */
    public function setAlg(string $alg): void
    {
        $this->alg = $alg;
    }

    /**
     * @param DateTime $last_use
     */
    public function setLastUse(DateTime $last_use): void
    {
        $this->last_use = $last_use;
    }

}