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
use App\Models\Utils\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineApiRepository")
 * @ORM\Table(name="oauth2_api")
 * Class Api
 * @package Models\OAuth2
 */
class Api extends BaseEntity
{

    /**
     * @ORM\Column(name="name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="description", type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="active", type="boolean")
     * @var bool
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="ApiScope", mappedBy="api", cascade={"persist"}, orphanRemoval=true)
     * @var ArrayCollection
     */
    private $scopes;

    /**
     * @ORM\OneToMany(targetEntity="ApiEndpoint", mappedBy="api", cascade={"persist"},orphanRemoval=true)
     * @var ArrayCollection
     *
     */
    private $endpoints;

    /**
     * @ORM\ManyToOne(targetEntity="ResourceServer", inversedBy="apis", cascade={"persist"})
     * @ORM\JoinColumn(name="resource_server_id", referencedColumnName="id")
     * @var ResourceServer
     */
    private $resource_server;

    /**
     * Api constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->scopes    = new ArrayCollection();
        $this->endpoints = new ArrayCollection();
    }

    public function getLogo()
    {
        $url = asset('/assets/img/apis/server.png');
        return $url;
    }

    public function getScopesStr():string
    {
        $scope = '';
        foreach ($this->scopes as $s) {
            if (!$s->isActive()) {
                continue;
            }
            $scope = $scope . $s->getName() . ' ';
        }
        $scope = trim($scope);

        return $scope;
    }

    /**
     * @return ArrayCollection
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    public function addScope(ApiScope $scope){
        if($this->scopes->contains($scope)) return;
        $this->scopes->add($scope);
        $scope->setApi($this);
    }

    /**
     * @return ArrayCollection
     */
    public function getEndpoints()
    {
        return $this->endpoints;
    }

    /**
     * @param ApiEndpoint $endpoint
     */
    public function addEndpoint(ApiEndpoint $endpoint){

        if($this->endpoints->contains($endpoint)) return;
        $this->endpoints->add($endpoint);
        $endpoint->setApi($this);
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
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
     * @return ResourceServer
     */
    public function getResourceServer(): ResourceServer
    {
        return $this->resource_server;
    }

    /**
     * @param ResourceServer $resource_server
     */
    public function setResourceServer(ResourceServer $resource_server): void
    {
        $this->resource_server = $resource_server;
    }

    /**
     * @return bool
     */
    public function hasResourceServer():bool{
        return $this->getResourceServerId() > 0;
    }

    public function getResourceServerId():int{
        try {
            return !is_null($this->resource_server) ? $this->resource_server->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        if($name == 'resource_server_id'){
            return $this->getResourceServerId();
        }
        return $this->{$name};
    }

}