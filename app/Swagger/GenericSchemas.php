<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class BearerAuthSchema
{
}

#[OA\Schema(
    schema: 'PaginateDataSchemaResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 6),
        new OA\Property(property: 'per_page', type: 'integer', example: 5),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 2),
    ],
    description: 'Base pagination metadata'
)]
class PaginateDataSchemaResponseSchema
{
}

#[OA\Schema(
    schema: 'Base',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Unique identifier', example: 1),
        new OA\Property(property: 'created_at', type: 'integer', description: 'Creation timestamp (epoch)', example: 1609459200),
        new OA\Property(property: 'updated_at', type: 'integer', description: 'Last update timestamp (epoch)', example: 1609459200),
    ],
    description: 'Base serializer fields'
)]
class BaseSchema
{
}