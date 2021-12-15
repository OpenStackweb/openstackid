<?php namespace Auth;
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
use App\libs\Auth\Models\IGroupSlugs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
use App\Models\Utils\BaseEntity;
use models\exceptions\ValidationException;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineGroupRepository")
 * @ORM\Table(name="`groups`")
 * @ORM\HasLifecycleCallbacks
 * Class Group
 * @package Auth
 */
class Group extends BaseEntity
{


    /**
     * @ORM\Column(name="name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="slug", type="string")
     * @var string
     */
    private $slug;

    /**
     * @ORM\Column(name="active", type="boolean")
     * @var bool
     */
    private $active;

    /**
     * @ORM\Column(name="is_default", type="boolean")
     * @var bool
     */
    private $default;

    /**
     * Many Groups have Many Users.
     * @ORM\ManyToMany(targetEntity="User", mappedBy="groups", fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $users;


    public function __construct()
    {
        parent::__construct();
        $this->active  = false;
        $this->default = false;
        $this->users   = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers(): ArrayCollection
    {
        return $this->users;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @param User $user
     */
    public function addUser(User $user){
        if($this->users->contains($user)) return;
        $this->users->add($user);
    }

    /**
     * @param User $user
     */
    public function removeUser(User $user){
        if(!$this->users->contains($user)) return;
        $this->users->removeElement($user);
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
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }

    /**
     * @ORM\PreRemove:
     */
    public function preRemoveHandler(LifecycleEventArgs $args){
        if(!self::canDelete($this->slug))
            throw new ValidationException(sprintf("can not delete group %s", $this->getSlug()));
    }


    /**
     * @param string $slug
     * @return bool
     */
    public static function canDelete(string $slug):bool{
        return !in_array($slug, [
            IGroupSlugs::RawUsersGroup,
            IGroupSlugs::SuperAdminGroup,
            IGroupSlugs::OAuth2ServerAdminGroup,
            IGroupSlugs::OpenIdServerAdminsGroup,
            IGroupSlugs::OAuth2SystemScopeAdminsGroup,
        ]);
    }


}