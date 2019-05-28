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
use OAuth2\Models\IUserConsent;
use Auth\User;
use App\Models\Utils\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="oauth2_user_consents")
 * Class UserConsent
 * @package Models\OAuth2
 */
class UserConsent extends BaseEntity implements IUserConsent {

    /**
     * @ORM\Column(name="scopes", type="string")
     * @var string
     */
    private $scopes;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\User", cascade={"persist"}, inversedBy="consents")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="Models\OAuth2\Client", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @var Client
     */
    private $client;

    /**
     * @return string
     */
    public function getScope():string
    {
        return $this->scopes;
    }

    /**
     * @return Client
     */
    public function getClient():Client
    {
        return $this->client;
    }

    /**
     * @return User
     */
    public function getUser():User
    {
        return $this->owner;
    }

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
     * @param string $scope
     */
    public function setScope(string $scope): void
    {
        $this->scopes = $scope;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }

}