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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineApiScopeRepository")
 * @ORM\Table(name="oauth2_api_scope")
 * Class ApiScope
 * @package Models\OAuth2
 */
class ApiScope extends BaseEntity
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
     * @ORM\Column(name="short_description", type="string")
     * @var string
     */
    private $short_description;

    /**
     * @ORM\Column(name="`active`", type="boolean")
     * @var bool
     */
    private $active;

    /**
     * @ORM\Column(name="`is_default`", type="boolean")
     * @var bool
     */
    private $default;

    /**
     * @ORM\Column(name="`system`", type="boolean")
     * @var bool
     */
    private $system;

    /**
     * @ORM\Column(name="`assigned_by_groups`", type="boolean")
     * @var bool
     */
    private $assigned_by_groups;

    /**
     * @ORM\ManyToMany(targetEntity="Models\OAuth2\ApiScopeGroup", mappedBy="scopes")
     */
    private $scope_groups;

    /**
     * @ORM\ManyToOne(targetEntity="Api", inversedBy="scopes")
     * @ORM\JoinColumn(name="api_id", referencedColumnName="id")
     * @var Api
     */
    private $api;


    public function __construct()
    {
        parent::__construct();
        $this->name = null;
        $this->description = null;
        $this->short_description = null;
        $this->active = false;
        $this->default = false;
        $this->system = false;
        $this->assigned_by_groups = false;
        $this->scope_groups = new ArrayCollection();
    }

    public function getScopeGroups(){
        return $this->scope_groups;
    }

    /**
     * @param ApiScopeGroup $scope_group
     */
    public function addToScopeGroup(ApiScopeGroup $scope_group){
        if($this->scope_groups->contains($scope_group)) return;
        $this->scope_groups->add($scope_group);
        $scope_group->addScope($this);
    }

    /**
     * @param ApiScopeGroup $scope_group
     */
    public function removeFromScopeGroup(ApiScopeGroup $scope_group){
        if(!$this->scope_groups->contains($scope_group)) return;
        $this->scope_groups->removeElement($scope_group);
        $scope_group->removeScope($this);
    }

    /**
     * @return Api|null
     */
    public function getApi(): ?Api
    {
        return $this->api;
    }

    /**
     * @param Api $api
     */
    public function setApi(Api $api): void
    {
        $this->api = $api;
    }

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
     * @return string
     */
    public function getApiName():string{
        return $this->hasApi() ? $this->getApi()->getName(): null;
    }

    /**
     * @return string
     */
    public function getApiDescription():string{
        return $this->hasApi() ? $this->getApi()->getDescription(): null;
    }

    /**
     * @return string
     */
    public function getApiLogo():string{
        return $this->hasApi() ? $this->getApi()->getLogo(): null;
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
     * @return string
     */
    public function getShortDescription(): string
    {
        return $this->short_description;
    }

    /**
     * @param string $short_description
     */
    public function setShortDescription(string $short_description): void
    {
        $this->short_description = $short_description;
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
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @param bool $default
     */
    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->system;
    }

    /**
     * @param bool $system
     */
    public function setSystem(bool $system): void
    {
        $this->system = $system;
    }

    /**
     * @return bool
     */
    public function isAssignedByGroups(): bool
    {
        return $this->assigned_by_groups;
    }

    /**
     * @param bool $assigned_by_groups
     */
    public function setAssignedByGroups(bool $assigned_by_groups): void
    {
        $this->assigned_by_groups = $assigned_by_groups;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        if($name == 'api_id')
            return $this->getApiId();
        return $this->{$name};
    }
}