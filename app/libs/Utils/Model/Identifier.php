<?php namespace Utils\Model;
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
interface Identifier
{
    /**
     * @return int
     */
    public function getLength():int;

    /**
     * @param string $value
     * @return $this
     */
    public function setValue(string $value);

    /**
     * @return int
     */
    public function getLifetime():int;

    /**
     * @return string
     */
    public function getType():string;

    /**
     * @return string
     */
    public function generateValue():string;
}