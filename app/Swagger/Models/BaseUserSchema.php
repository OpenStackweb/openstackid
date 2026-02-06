<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BaseUser',
    title: 'Base User',
    description: 'Base User serialized representation',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Base'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'first_name', type: 'string', description: 'First name', example: 'John'),
                new OA\Property(property: 'last_name', type: 'string', description: 'Last name', example: 'Doe'),
                new OA\Property(property: 'pic', type: 'string', format: 'uri', description: 'Profile picture URL'),
            ]
        )
    ]
)]
class BaseUserSchema
{
}
