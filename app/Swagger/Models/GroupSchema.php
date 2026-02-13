<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Group',
    type: 'object',
    description: 'Group API response - serialized representation of a group',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Base'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'Group name', example: 'Administrators'),
                new OA\Property(property: 'slug', type: 'string', description: 'Group slug for URL-friendly identification', example: 'administrators'),
                new OA\Property(property: 'active', type: 'boolean', description: 'Whether the group is active', example: true),
                new OA\Property(property: 'default', type: 'boolean', description: 'Whether this is a default group', example: false),
            ]
        )
    ]
)]
class GroupSchema {}
