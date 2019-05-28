<?php namespace OpenId\Models;
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
use DateTime;
/**
 * Class Association
 * @package OpenId\Models
 */
class Association implements IAssociation
{
    /**
     * @var string
     */
    private $handle;
    /**
     * @var string
     */
    private $secret;
    /**
     * @var string
     */
    private $mac_function;
    /**
     * @var int
     */
    private $lifetime;
    /**
     * @var DateTime
     */
    private $issued;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $realm;

    /**
     * Association constructor.
     * @param string $handle
     * @param string $secret
     * @param string $mac_function
     * @param int $lifetime
     * @param DateTime $issued
     * @param string $type
     * @param string $realm
     */
    public function __construct
    (
        ?string $handle = null,
        ?string $secret = null ,
        ?string $mac_function = null,
        ?string $lifetime = null,
        ?DateTime $issued = null,
        ?string $type = null,
        ?string $realm = null
    )
    {
        $this->handle = $handle;
        $this->secret = $secret;
        $this->mac_function = $mac_function;
        $this->lifetime = $lifetime;
        $this->issued = $issued;
        $this->type = $type;
        $this->realm = $realm;
    }

    public function getMacFunction():string
    {
        return $this->mac_function;
    }

    public function setMacFunction(string $mac_function):void
    {
        $this->mac_function = $mac_function;
    }

    public function getSecret():string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
       $this->secret = $secret;
    }

    public function getLifetime():int
    {
        return intval($this->lifetime);
    }

    public function setLifetime(int $lifetime):void
    {
        $this->lifetime = $lifetime;
    }

    public function getIssued():DateTime
    {
        return $this->issued;
    }

    public function setIssued(DateTime $issued):void
    {
        $this->issued = $issued;
    }

    public function getType():int
    {
        return $this->type;
    }

    public function setType(int $type):void
    {
        $this->type = $type;
    }

    public function getRealm():?string
    {
        return $this->realm;
    }

    public function setRealm(string $realm):void
    {
        $this->realm = $realm;
    }

    public function IsExpired():bool
    {
        // TODO: Implement IsExpired() method.
    }

    public function getRemainingLifetime():int
    {
        // TODO: Implement getRemainingLifetime() method.
    }

    public function getHandle():string
    {
        return $this->handle;
    }

    /**
     * @return int
     */
    public function getId()
    {
        // TODO: Implement getId() method.
    }

    /**
     * @return int
     */
    public function getIdentifier()
    {
        // TODO: Implement getIdentifier() method.
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        // TODO: Implement isNew() method.
    }
}