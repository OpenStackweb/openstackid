<?php namespace App\libs\OAuth2;

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


/**
 * Interface IUserScopes
 * @package App\libs\OAuth2
 */
interface IUserScopes
{
    const Profile = 'profile';
    const Email   = 'email';
    const Address = 'address';
    const Registration = 'user-registration';
    const ReadAll = 'users-read-all';
    const SSO = 'sso';
    const MeRead = 'me/read';
    const MeWrite = 'me/write';
    const Write = 'users/write';
}