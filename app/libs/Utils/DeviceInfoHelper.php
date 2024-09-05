<?php namespace App\libs\Utils;
/*
 * Copyright 2024 OpenStack Foundation
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


use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Illuminate\Support\Facades\Log;

/**
 * Class DeviceInfoHelper
 * @package App\libs\Utils
 */
final class DeviceInfoHelper
{
    public static function getDeviceInfo():string
    {
        AbstractDeviceParser::setVersionTruncation(AbstractDeviceParser::VERSION_TRUNCATION_NONE);

        $userAgent = $_SERVER['HTTP_USER_AGENT']; // change this to the useragent you want to parse
        $clientHints = ClientHints::factory($_SERVER); // client hints are optional

        $dd = new DeviceDetector($userAgent, $clientHints);

        $dd->parse();

        if ($dd->isBot()) {
            // handle bots,spiders,crawlers,...
            $botInfo = $dd->getBot();
            return sprintf("Bot %s", json_encode($botInfo));
        } else {
            $osInfo = $dd->getOs();
            $device = $dd->getDeviceName();
            $brand  = $dd->getBrandName();
            $model  = $dd->getModel();
            Log::debug
            (
                sprintf
                (
                    "Device Info: %s %s %s %s",
                    json_encode($device),
                    json_encode($brand),
                    json_encode($model),
                    json_encode($osInfo)
                )
            );
            //return sprintf("%s %s %s %s", $device, $brand, $model, $osInfo);
            return 'TBD';
        }
    }
}