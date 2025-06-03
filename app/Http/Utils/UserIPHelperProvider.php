<?php namespace App\Http\Utils;
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

use Illuminate\Support\Facades\Log;
/**
 * Class UserIPHelperProvider
 * @package App\Http\Utils
 */
final class UserIPHelperProvider implements IUserIPHelperProvider
{

    public function getCurrentUserIpAddress(): string
    {
        Log::debug(sprintf("UserIPHelperProvider::getCurrentUserIpAddress HTTP_CLIENT_IP %s HTTP_X_FORWARDED_FOR %s REMOTE_ADDR %s",
            $_SERVER['HTTP_CLIENT_IP'] ?? 'N/A',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'N/A',
            $_SERVER['REMOTE_ADDR'] ?? 'N/A'
        ));
        $ip = $_SERVER['HTTP_CLIENT_IP'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''));
        Log::debug(sprintf("UserIPHelperProvider::getCurrentUserIpAddress ip %s", $ip));
        return $ip;
    }
}