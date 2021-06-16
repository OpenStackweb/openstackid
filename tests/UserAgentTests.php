<?php namespace Tests;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Http\Utils\CookieSameSitePolicy;
/**
 * Class UserAgentTests
 */
class UserAgentTests extends TestCase
{
    public function testChromiumVersion12(){
        $agent_str = <<<AGENT
Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.30 (KHTML, like Gecko) Ubuntu/11.04 Chromium/12.0.742.112 Chrome/12.0.742.112 Safari/534.30
AGENT;

        $this->assertTrue(CookieSameSitePolicy::isChromiumBased($agent_str));

        $this->assertFalse(CookieSameSitePolicy::dropsUnrecognizedSameSiteCookies($agent_str));
    }

    public function testChromiumVersion53(){
        $agent_str = <<<AGENT
Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/53.0.2785.143 Chrome/53.0.2785.143 Safari/537.36
AGENT;

        $this->assertTrue(CookieSameSitePolicy::isChromiumBased($agent_str));

        $this->assertTrue(CookieSameSitePolicy::dropsUnrecognizedSameSiteCookies($agent_str));
    }
}