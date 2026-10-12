<?php namespace Tests\unit;

/**
 * Copyright 2026 OpenStack Foundation
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

use LaravelDoctrine\ORM\Facades\EntityManager;
use models\exceptions\ValidationException;
use Models\OAuth2\ApiScopeGroup;
use OAuth2\Services\IApiScopeGroupService;
use Tests\BrowserKitTestCase;

/**
 * Class ApiScopeGroupServiceTest
 * A duplicated group name must surface as a ValidationException (HTTP 412 in
 * ApiScopeGroupController), not as an unhandled exception (HTTP 500).
 * @package Tests\unit
 */
class ApiScopeGroupServiceTest extends BrowserKitTestCase
{
    private function persistGroup(string $name): ApiScopeGroup
    {
        $group = new ApiScopeGroup();
        $group->setName($name);
        $group->setActive(true);
        $group->setDescription('test description');
        EntityManager::persist($group);
        EntityManager::flush();
        return $group;
    }

    public function testUpdateWithNameUsedByAnotherGroupThrowsValidationException()
    {
        $suffix = uniqid();
        $this->persistGroup("group_a_$suffix");
        $group_b = $this->persistGroup("group_b_$suffix");

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("group_a_$suffix");

        app(IApiScopeGroupService::class)->update($group_b->getId(), ['name' => "group_a_$suffix"]);
    }

    public function testCreateWithExistingNameThrowsValidationException()
    {
        $name = 'group_dup_' . uniqid();
        $this->persistGroup($name);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($name);

        app(IApiScopeGroupService::class)->create([
            'name'   => $name,
            'scopes' => '',
            'users'  => '',
        ]);
    }
}
