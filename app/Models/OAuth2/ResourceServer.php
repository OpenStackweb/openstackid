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
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineResourceServerRepository")
 * @ORM\Table(name="oauth2_resource_server")
 * Class ResourceServer
 * @package Models\OAuth2
 */
class ResourceServer extends BaseEntity
{

    /**
     * @ORM\Column(name="friendly_name", type="string")
     * @var string
     */
    private $friendly_name;

    /**
     * @ORM\Column(name="host", type="string")
     * @var string
     */
    private $host;

    /**
     * @ORM\Column(name="ips", type="string")
     * @var string
     */
    private $ips;

    /**
     * @ORM\Column(name="active", type="boolean")
     * @var bool
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="Api", mappedBy="resource_server", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @var Api[]
     */
    private $apis;

    /**
     * @ORM\OneToOne(targetEntity="Models\OAuth2\Client", mappedBy="resource_server", cascade={"persist", "remove"})
     * @var Client
     */
    private $client;

    /**
     * @param string $ip
     * @return bool
     */
    public function isOwn($ip)
    {
        $ips = explode(',',  $this->ips);
        return in_array($ip, $ips);
    }

    /**
     * @return string
     */
    public function getIPAddresses()
    {
       return $this->ips;
    }

    public function __construct()
    {
        parent::__construct();
        $this->active = false;
        $this->apis = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getFriendlyName(): string
    {
        return $this->friendly_name;
    }

    /**
     * @param string $friendly_name
     */
    public function setFriendlyName(string $friendly_name): void
    {
        $this->friendly_name = $friendly_name;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getIps(): string
    {
        return $this->ips;
    }

    /**
     * @param string $ips
     */
    public function setIps(string $ips): void
    {
        $this->ips = $ips;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return Api[]
     */
    public function getApis()
    {
        return $this->apis;
    }

    /**
     * @param Api $api
     */
    public function addApi(Api $api){
        if($this->apis->contains($api)) return;
        $this->apis->add($api);
        $api->setResourceServer($this);
    }

    /**
     * @param Api $api
     */
    public function removeApi(Api $api){
        if(!$this->apis->contains($api)) return;
        $this->apis->removeElement($api);
    }

    public function setClient(Client $client){
        $this->client = $client;
        $client->setResourceServer($this);
    }

    /**
     * @return Client|null
     */
    public function getClient():?Client
    {
        return $this->client;
    }

    /**
     * @return int
     */
    public function getClientId(): int{
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
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }
}
