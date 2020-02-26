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
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
use App\Models\Utils\BaseEntity;
/**
 * @ORM\Entity
 * @ORM\Table(name="affiliations")
 * Class Affiliation
 * @package Auth
 */
class Affiliation extends BaseEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="Auth\User", cascade={"persist"}, inversedBy="affiliations")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\Column(name="job_title", type="string")
     * @var string
     */
    private $job_title;

    /**
     * @ORM\Column(name="role", type="string")
     * @var string
     */
    private $role;

    /**
     * @var \DateTime
     * @ORM\Column(name="start_date", type="datetime")
     */
    protected $start_date;

    /**
     * @var \DateTime
     * @ORM\Column(name="end_date", type="datetime")
     */
    protected $end_date;

    /**
     * @ORM\Column(name="is_current", type="boolean")
     * @var boolean
     */
    private $is_current;

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     */
    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    /**
     * @return string
     */
    public function getJobTitle(): string
    {
        return $this->job_title;
    }

    /**
     * @param string $job_title
     */
    public function setJobTitle(string $job_title): void
    {
        $this->job_title = $job_title;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->start_date;
    }

    /**
     * @param \DateTime $start_date
     */
    public function setStartDate(\DateTime $start_date): void
    {
        $this->start_date = $start_date;
    }

    /**
     * @return ?\DateTime
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->end_date;
    }

    /**
     * @param \DateTime $end_date
     */
    public function setEndDate(\DateTime $end_date): void
    {
        $this->end_date = $end_date;
    }

    /**
     * @return bool
     */
    public function isCurrent(): bool
    {
        return $this->is_current;
    }

    /**
     * @param bool $is_current
     */
    public function setIsCurrent(bool $is_current): void
    {
        $this->is_current = $is_current;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }

}