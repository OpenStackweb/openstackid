<?php namespace Tests;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\libs\Utils\PunnyCodeHelper;


/**
 * Class PunnyCodeHelperTest
 * @package Tests
 */
class PunnyCodeHelperTest extends BrowserKitTestCase
{
    public function testDecode(){
        $original = 'hei@やる.ca';

        $encoded2 = PunnyCodeHelper::encodeEmail($original);
        $original2 = PunnyCodeHelper::decodeEmail($encoded2);

        $this->assertTrue(!empty($original));
        $this->assertTrue($original2 === $original);
    }
}