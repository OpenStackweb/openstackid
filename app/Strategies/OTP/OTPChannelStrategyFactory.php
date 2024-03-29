<?php namespace App\Strategies\OTP;
/**
 * Copyright 2021 OpenStack Foundation
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

use App\Services\Auth\IUserService;
use Auth\Repositories\IUserRepository;
use Illuminate\Support\Facades\App;
use OAuth2\Exceptions\InvalidOAuth2Request;
use OAuth2\OAuth2Protocol;
/**
 * Class OTPChannelStrategyFactory
 * @package App\Strategies\OTP
 */
final class OTPChannelStrategyFactory
{
    public static function build(string $connection):IOTPChannelStrategy{

        switch ($connection) {
            case OAuth2Protocol::OAuth2PasswordlessConnectionEmail:
                return new OTPChannelEmailStrategy
                (
                    App::make(IUserService::class),
                    App::make(IUserRepository::class),
                );
            case OAuth2Protocol::OAuth2PasswordlessConnectionInline:
                return new OTPChannelNullStrategy();
        }
        throw new InvalidOAuth2Request(sprintf("connection value %s is not valid", $connection));
    }
}