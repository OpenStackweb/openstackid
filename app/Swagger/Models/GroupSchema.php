<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Group',
    title: 'Group',
    description: 'Group serialized representation',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Base'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'Group name'),
                new OA\Property(property: 'slug', type: 'string', description: 'Group slug'),
                new OA\Property(property: 'active', type: 'boolean', description: 'Whether the group is active'),
                new OA\Property(property: 'default', type: 'boolean', description: 'Whether the group is a default group'),
            ]
        )
    ]
)]
class GroupSchema
{
}
