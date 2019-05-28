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
use Auth\User;
use OpenId\Models\ITrustedSite;
use App\Models\Utils\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineOpenIdTrustedSiteRepository")
 * @ORM\Table(name="openid_trusted_sites")
 * Class OpenIdTrustedSite
 * @package Models\OpenId
 */
class OpenIdTrustedSite extends BaseEntity implements ITrustedSite
{

    /**
     * @ORM\Column(name="realm", type="string")
     * @var string
     */
    private $realm;

    /**
     * @ORM\Column(name="data", type="string")
     * @var string
     */
    private $data;

    /**
     * @ORM\Column(name="policy", type="string")
     * @var string
     */
    private $policy;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\User", cascade={"persist"}, inversedBy="trusted_sites")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    private $owner;

    /**
     * @return string
     */
    public function getUITrustedData():string
    {
        $data = $this->getData();
        $str  = '';
        foreach ($data as $val) {
            $str .= $val . ', ';
        }
        return trim($str, ', ');
    }

    /**
     * @return mixed|string
     */
    public function getData()
    {
        $res = is_null($this->data)?'[]':$this->data;
        return json_decode($res);
    }

    /**
     * @param string $data
     */
    public function setData(string $data){
        $this->data = $data;
    }

    /**
     * @return User
     */
    public function getUser():User
    {
        return $this->owner;
    }

    /**
     * @return string
     */
    public function getAuthorizationPolicy():string
    {
        return $this->policy;
    }

    /**
     * @return string
     */
    public function getRealm(): string
    {
        return $this->realm;
    }

    /**
     * @param string $realm
     */
    public function setRealm(string $realm): void
    {
        $this->realm = $realm;
    }

    /**
     * @return string
     */
    public function getPolicy(): string
    {
        return $this->policy;
    }

    /**
     * @param string $policy
     */
    public function setPolicy(string $policy): void
    {
        $this->policy = $policy;
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
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }

}