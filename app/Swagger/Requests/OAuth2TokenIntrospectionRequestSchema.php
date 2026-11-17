<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OAuth2TokenIntrospectionRequest',
    title: 'OAuth2 Token Introspection Request',
    description: 'Token introspection request parameters per RFC 7662 §2.1',
    type: 'object',
    required: ['token'],
    properties: [
        new OA\Property(property: 'token', type: 'string', description: 'The token to introspect'),
        new OA\Property(property: 'client_id', type: 'string', description: 'Client identifier (if not using HTTP Basic auth)'),
        new OA\Property(property: 'client_secret', type: 'string', description: 'Client secret (if not using HTTP Basic auth)'),
    ]
)]
class OAuth2TokenIntrospectionRequestSchema
{
}
