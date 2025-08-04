<?php

/**
 * Copyright 2025 OpenStack Foundation
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

use App\libs\OAuth2\IGroupScopes;
use Tests\OAuth2ProtectedApiTestCase;

/**
 * Class OAuth2GroupApiTest
 */
final class OAuth2GroupApiTest extends OAuth2ProtectedApiTestCase
{
    public function testGetAll(){
        $params = [
            'filter' => 'slug==sponsors-services||sponsors||sponsors-external-users',
            'order' => '-slug'
        ];

        $response = $this->action(
            "GET",
            "Api\\OAuth2\\OAuth2GroupApiController@getAll",
            $params,
            [],
            [],
            [],
            array("HTTP_Authorization" => " Bearer " .$this->access_token));

        $content   = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue($page->total > 0);
    }

    protected function getScopes()
    {
        return [
            IGroupScopes::ReadAll,
            IGroupScopes::Write
        ];
    }
}