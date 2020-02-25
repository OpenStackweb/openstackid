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
use models\utils\IEntity;
/**
 * Interface IAssociation
 * @package OpenId\Models
 */
interface IAssociation extends IEntity {

    const TypePrivate = 1;
    const TypeSession = 2;


    /**
     * @return string
     */
    public function getMacFunction(): string;

    /**
     * @param string $mac_function
     */
    public function setMacFunction(string $mac_function): void;

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void;

    /**
     * @return string
     */
    public function getSecret(): ?string;

    /**
     * @return int
     */
    public function getLifetime():int;

    /**
     * @param int $lifetime
     */
    public function setLifetime(int $lifetime): void;

    /**
     * @return \DateTime
     */
    public function getIssued(): \DateTime;

    /**
     * @param \DateTime $issued
     */
    public function setIssued(\DateTime $issued): void;

    /**
     * @return int
     */
    public function getType():int;

    /**
     * @param int $type
     */
    public function setType(int $type): void;

    /**
     * @return string
     */
    public function getRealm():?string;

    /**
     * @param string $realm
     */
    public function setRealm(string $realm): void;

    /**
     * @return bool
     */
    public function IsExpired():bool;

    /**
     * @return int
     */
    public function getRemainingLifetime():int;

    /**
     * @return string
     */
	public function getHandle():string;

}