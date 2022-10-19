<?php namespace OAuth2\Services;
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
use OAuth2\Requests\OAuth2RequestMemento;
/**
 * Interface IMementoOAuth2SerializerService
 * @package OAuth2\Services
 */
interface IMementoOAuth2SerializerService {

    /**
     * @param OAuth2RequestMemento $memento
     * @return void
     */
    public function serialize(OAuth2RequestMemento $memento):void;

    /**
     * @return OAuth2RequestMemento|null
     */
    public function load():?OAuth2RequestMemento;

    /**
     * @return void
     */
    public function forget():void;

    /**
     * @return bool
     */
    public function exists():bool;
} 