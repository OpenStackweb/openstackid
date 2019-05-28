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
use App\Models\Utils\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineServerExtensionRepository")
 * @ORM\Table(name="server_extensions")
 * Class ServerExtension
 * @package Models\OpenId
 */
class ServerExtension extends BaseEntity
{
    /**
     * @ORM\Column(name="name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="namespace", type="string")
     * @var string
     */
    private $namespace;

    /**
     * @ORM\Column(name="active", type="boolean")
     * @var bool
     */
    private $active;

    /**
     * @ORM\Column(name="extension_class", type="string")
     * @var string
     */
    private $extension_class;

    /**
     * @ORM\Column(name="description", type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="view_name", type="string")
     * @var string
     */
    private $view_name;

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
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
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
     * @return string
     */
    public function getExtensionClass(): string
    {
        return $this->extension_class;
    }

    /**
     * @param string $extension_class
     */
    public function setExtensionClass(string $extension_class): void
    {
        $this->extension_class = $extension_class;
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
    public function getViewName(): string
    {
        return $this->view_name;
    }

    /**
     * @param string $view_name
     */
    public function setViewName(string $view_name): void
    {
        $this->view_name = $view_name;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }


}