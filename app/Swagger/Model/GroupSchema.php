<?php

namespace App\Swagger\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GroupModel',
    type: 'object',
    description: 'Group database model - represents the original entity structure',
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
            property: 'is_default',
            type: 'boolean',
            description: 'Whether this is a default group',
            example: false
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            description: 'Creation timestamp'
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            description: 'Last update timestamp'
        ),
    ]
)]
class GroupSchema {}
