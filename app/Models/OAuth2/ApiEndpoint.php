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
 * @package Models\OAuth2
 */
#[ORM\Table(name: 'oauth2_api_endpoint')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineApiEndpointRepository::class)]
class ApiEndpoint extends BaseEntity {

    /**
     * attributes
     */
    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'string')]
    private $description;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'active', type: 'boolean')]
    private $active;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'allow_cors', type: 'boolean')]
    private $allow_cors;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'allow_credentials', type: 'boolean')]
    private $allow_credentials;

    /**
     * @var string
     */
    #[ORM\Column(name: 'route', type: 'string')]
    private $route;

    /**
     * @var string
     */
    #[ORM\Column(name: 'http_method', type: 'string')]
    private $http_method;

    /**
     * @var int
     */
    #[ORM\Column(name: 'rate_limit', type: 'integer')]
    private $rate_limit;

    /**
     * @var int
     */
    #[ORM\Column(name: 'rate_limit_decay', type: 'integer')]
    private $rate_limit_decay;

    /**
     * @var Api
     */
    #[ORM\JoinColumn(name: 'api_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Api::class, inversedBy: 'endpoints', cascade: ['persist'])]
    private $api;

    /**
     * @var ApiScope[]
     */
    #[ORM\JoinTable(name: 'oauth2_api_endpoint_api_scope')]
    #[ORM\JoinColumn(name: 'api_endpoint_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: \ApiScope::class)]
    private $scopes;

    /**
     * ApiEndpoint constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->rate_limit       = 0;
        $this->rate_limit_decay = 0;
        $this->active = false;
        $this->allow_cors = false;
        $this->allow_credentials = false;
        $this->scopes = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getRoute():string
    {
        return $this->route;
    }

    public function getHttpMethod(){
        return $this->http_method;
    }

    public function setRoute($route)
    {
        $this->route = $route;
    }

    public function setHttpMethod($http_method)
    {
        $this->http_method = $http_method;
    }

    public function getScope():string
    {
        $scope = '';
        foreach($this->scopes as $s){
            if(!$s->isActive()) continue;
            $scope = $scope .$s->getName().' ';
        }
        $scope = trim($scope);
        return $scope;
    }

    public function isActive()
    {
        return $this->active;
    }

    public function setStatus($active)
    {
        $this->active = $active;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name= $name;
    }


    public function supportCORS():bool
    {
        return $this->allow_cors;
    }

    /**
     * @return bool
     */
    public function supportCredentials():bool
    {
        return $this->allow_credentials;
    }

    /**
     * @param ApiScope $scope
     */
    public function addScope(ApiScope $scope){
        if($this->scopes->contains($scope)) return;
        $this->scopes->add($scope);
    }

    /**
     * @param ApiScope $scope
     */
    public function removeScope(ApiScope $scope){
        if(!$this->scopes->contains($scope)) return;
        $this->scopes->removeElement($scope);
    }

    /**
     * @return Api
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param Api $api
     */
    public function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * @return ApiScope[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param ApiScope[] $scopes
     */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
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
    public function isAllowCors(): bool
    {
        return $this->allow_cors;
    }

    /**
     * @param bool $allow_cors
     */
    public function setAllowCors(bool $allow_cors): void
    {
        $this->allow_cors = $allow_cors;
    }

    /**
     * @return bool
     */
    public function isAllowCredentials(): bool
    {
        return $this->allow_credentials;
    }

    /**
     * @param bool $allow_credentials
     */
    public function setAllowCredentials(bool $allow_credentials): void
    {
        $this->allow_credentials = $allow_credentials;
    }

    /**
     * @return int
     */
    public function getRateLimit(): int
    {
        return $this->rate_limit;
    }

    /**
     * @param int $rate_limit
     */
    public function setRateLimit(int $rate_limit): void
    {
        $this->rate_limit = $rate_limit;
    }

    /**
     * @return int
     */
    public function getRateLimitDecay(): int
    {
        return $this->rate_limit_decay;
    }

    /**
     * @param int $rate_limit_decay
     */
    public function setRateLimitDecay(int $rate_limit_decay): void
    {
        $this->rate_limit_decay = $rate_limit_decay;
    }

    /**
     * @param ApiScope $scope
     * @return bool
     */
    public function hasScope(ApiScope $scope):bool{
        return $this->scopes->contains($scope);
    }

    /**
     * @return bool
     */
    public function hasApi():bool{
        return $this->getApiId() > 0;
    }

    public function getApiId():int{
        try {
            return !is_null($this->api) ? $this->api->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        if($name == "api_id"){
            return $this->getApiId();
        }
        return $this->{$name};
    }
}