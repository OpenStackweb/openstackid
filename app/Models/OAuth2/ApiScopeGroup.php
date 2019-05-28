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
use Auth\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineApiScopeGroupRepository")
 * @ORM\Table(name="oauth2_api_scope_group")
 * Class ApiScope
 * @package Models\OAuth2
 */
class ApiScopeGroup extends BaseEntity
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
     * @ORM\ManyToMany(targetEntity="ApiScope", cascade={"persist"})
     * @ORM\JoinTable(name="oauth2_api_scope_group_scope",
     *      joinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="scope_id", referencedColumnName="id")}
     *     )
     * @var ApiScope[]
     */
    private $scopes;

    /**
     * @ORM\ManyToMany(targetEntity="Auth\User", cascade={"persist"})
     * @ORM\JoinTable(name="oauth2_api_scope_group_users",
     *      joinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     *     )
     * @var User[]
     */
    private $users;

    /**
     * ApiEndpoint constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->scopes      = new ArrayCollection();
        $this->users       = new ArrayCollection();
        $this->description = "";
        $this->active      = false;
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
     * @return ApiScope[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    public function clearScopes(){
        $this->scopes->clear();
    }

    /**
     * @param ApiScope $scope
     * @return bool
     */
    public function hasScope(ApiScope $scope):bool{
        return $this->scopes->contains($scope);
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
     * @return User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function clearUsers(){
        $this->users->clear();
    }

    /**
     * @param User $user
     */
    public function addUser(User $user){
        if($this->users->contains($user)) return;
        $this->users->add($user);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }

}