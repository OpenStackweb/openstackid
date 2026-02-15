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
use Illuminate\Support\Facades\Log;

/**
 * @package Models\OAuth2
 */
#[ORM\Table(name: 'oauth2_resource_server')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineResourceServerRepository::class)]
class ResourceServer extends BaseEntity
{

    /**
     * @var string
     */
    #[ORM\Column(name: 'friendly_name', type: 'string')]
    private $friendly_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'host', type: 'string')]
    private $host;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ips', type: 'string')]
    private $ips;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'active', type: 'boolean')]
    private $active;

    /**
     * @var Api[]
     */
    #[ORM\OneToMany(targetEntity: \Api::class, mappedBy: 'resource_server', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $apis;

    /**
     * @var Client
     */
    #[ORM\OneToOne(targetEntity: \Models\OAuth2\Client::class, mappedBy: 'resource_server', cascade: ['persist', 'remove'])]
    private $client;

    /**
     * @param string $ip
     * @return bool
     */
    public function isOwn($ip)
    {
        if (!config('oauth2.validate_resource_server_ip', true)) return true;

        $provided_ips = array_map('trim', explode(',', $ip));
        $own_ips = array_map('trim', explode(',', $this->ips));
        Log::debug
        (
            sprintf
            (
                "ResourceServer::isOwn resource server %s checking if %s is in %s",
                $this->id,
                $ip,
                $this->ips
            )
        );
        foreach ($provided_ips as $provided_ip){
            if(in_array($provided_ip, $own_ips)) return true;
        }
        return false;
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
    public function __get($name)
    {
        return $this->{$name};
    }

    /**
     * @param Client $client
     * @return bool
     */
    public function canImpersonateClient(Client $client): bool
    {
        return true;
    }
}
