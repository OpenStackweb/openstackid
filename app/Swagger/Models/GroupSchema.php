<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Group',
    type: 'object',
    description: 'Group API response - serialized representation of a group',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            description: 'Unique identifier',
            example: 1
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            description: 'Group name',
            example: 'Administrators'
        ),
        new OA\Property(
            property: 'slug',
            type: 'string',
            description: 'Group slug for URL-friendly identification',
            example: 'administrators'
        ),
        new OA\Property(
            property: 'active',
            type: 'boolean',
            description: 'Whether the group is active',
            example: true
        ),
        new OA\Property(
            property: 'default',
            type: 'boolean',
            description: 'Whether this is a default group',
            example: false
        ),
        new OA\Property(
            property: 'created_at',
            type: 'integer',
            description: 'Creation timestamp (Unix epoch)',
            example: 1704067200
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'integer',
            description: 'Last update timestamp (Unix epoch)',
            example: 1704153600
        ),
    ]
)]
class GroupSchema {}
