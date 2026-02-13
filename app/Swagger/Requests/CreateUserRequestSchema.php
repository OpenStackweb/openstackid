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
    schema: 'CreateUserRequest',
    title: 'Create User Request',
    description: 'Request body for creating a new user. Only email is required, all other fields are optional.',
    type: 'object',
    required: ['email'],
    allOf: [
        new OA\Schema(ref: '#/components/schemas/UserFields')
    ]
)]
class CreateUserRequestSchema
{
}
