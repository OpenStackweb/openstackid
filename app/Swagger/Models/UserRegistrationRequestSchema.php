<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserRegistrationRequest',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Base'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'email', type: 'string', description: 'Email address'),
                new OA\Property(property: 'first_name', type: 'string', description: 'First name'),
                new OA\Property(property: 'last_name', type: 'string', description: 'Last name'),
                new OA\Property(property: 'country', type: 'string', description: 'Country ISO alpha-2 code'),
                new OA\Property(property: 'hash', type: 'string', description: 'Registration request hash'),
                new OA\Property(property: 'set_password_link', type: 'string', format: 'uri', description: 'Link to set password'),
            ]
        )
    ]
)]
class UserRegistrationRequestSchema
{
}
