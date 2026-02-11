<?php namespace App\Swagger\schemas;
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

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserRegistrationRequestFields',
    title: 'User Registration Request Fields',
    description: 'Common fields for user registration request operations',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'first_name',
            type: 'string',
            description: 'First name',
            maxLength: 100,
            nullable: true
        ),
        new OA\Property(
            property: 'last_name',
            type: 'string',
            description: 'Last name',
            maxLength: 100,
            nullable: true
        ),
        new OA\Property(
            property: 'company',
            type: 'string',
            description: 'Company name',
            maxLength: 100,
            nullable: true
        ),
    ]
)]
class UserRegistrationRequestFieldsSchema
{
}
