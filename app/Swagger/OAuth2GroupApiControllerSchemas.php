<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedGroupResponseSchema',
    type: 'object',
    description: 'Paginated list of groups',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    description: 'Array of group objects',
                    items: new OA\Items(ref: '#/components/schemas/Group')
                )
            ]
        )
    ]
)]
class PaginatedGroupResponseSchemaSchema {}
