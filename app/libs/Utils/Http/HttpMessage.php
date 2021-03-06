<?php namespace Utils\Http;
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
use Utils\IPHelper;
use ArrayAccess;
/**
 * Class HttpMessage
 * @package Utils\Http
 */
class HttpMessage implements ArrayAccess
{

    /**
     * http://php.net/manual/en/language.variables.external.php
     * Dots and spaces in variable names are converted to underscores.
     * For example <input name="a.b" /> becomes $_REQUEST["a_b"].
     */
    const PHP_REQUEST_VAR_SEPARATOR = "_";
    /**
     * @var array
     */
    protected $container = [];

    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $this->container = $values;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @param mixed $offset
     * @return null
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode(array_merge(['from_ip' => IPHelper::getUserIp()], $this->container));
    }
}
