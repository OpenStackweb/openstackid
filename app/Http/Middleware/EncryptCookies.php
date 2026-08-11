<?php namespace App\Http\Middleware;
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
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;
use OAuth2\Services\IPrincipalService;
/**
 * Class EncryptCookies
 * @package App\Http\Middleware
 */
class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * The trusted-device token is a high-entropy random secret only ever compared
     * against a server-side SHA-256 hash, so cookie-layer encryption adds no
     * meaningful protection - exclude it so the value round-trips verbatim.
     *
     * @var array
     */
    protected $except = [
        IPrincipalService::OP_BROWSER_STATE_COOKIE_NAME,
    ];

    public function __construct(Encrypter $encrypter)
    {
        parent::__construct($encrypter);
        $this->except[] = config('two_factor.cookie_name', 'device_trust_token');
    }

}
