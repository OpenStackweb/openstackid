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
use OAuth2\Models\IClientPublicKey;
use jwk\impl\RSAJWKFactory ;
use jwk\impl\RSAJWKPEMPublicKeySpecification;
use jwk\IJWK;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package Models\OAuth2
 */
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineClientPublicKeyRepository::class)] // Class ClientPublicKey
class ClientPublicKey extends AsymmetricKey implements IClientPublicKey
{

    /**
     * @var Client
     */
    #[ORM\JoinColumn(name: 'oauth2_client_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Models\OAuth2\Client::class, inversedBy: 'public_keys')]
    private $owner;

    /**
     * @return Client
     */
    public function getOwner():Client
    {
        return $this->owner;
    }

    /**
     * @param \Models\OAuth2\Client $owner
     */
    public function setOwner(Client $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @param string $kid
     * @param string $type
     * @param string $use
     * @param string $pem
     * @param string $alg
     * @param bool $active
     * @param \DateTime $valid_from
     * @param \DateTime $valid_to
     * @return IClientPublicKey
     */
    static public function buildFromPEM($kid, $type, $use, $pem, $alg, $active, \DateTime $valid_from, \DateTime $valid_to)
    {
        $pk = new self;
        $pk->kid = $kid;
        $pk->pem_content = $pem;
        $pk->type = $type;
        $pk->usage = $use;
        $pk->alg   = $alg;
        $pk->active = $active;
        $pk->valid_from = $valid_from;
        $pk->valid_to = $valid_to;
        return $pk;
    }

    public function getPublicKeyPEM()
    {
       return $this->pem_content;
    }

    /**
     * @return IJWK
     */
    public function toJWK()
    {
        $jwk = RSAJWKFactory::build
        (
            new RSAJWKPEMPublicKeySpecification
            (
                $this->getPublicKeyPEM(),
                $this->alg
            )
        );

        $jwk->setId($this->kid);
        $jwk->setType($this->type);
        $jwk->setKeyUse($this->usage);
        return $jwk;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }
}