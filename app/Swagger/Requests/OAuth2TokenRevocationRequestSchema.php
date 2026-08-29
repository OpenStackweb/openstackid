<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OAuth2TokenRevocationRequest',
    title: 'OAuth2 Token Revocation Request',
    description: 'Token revocation request parameters per RFC 7009 §2.1',
    type: 'object',
    required: ['token'],
    properties: [
        new OA\Property(property: 'token', type: 'string', description: 'The token to revoke'),
        new OA\Property(property: 'token_type_hint', type: 'string', description: 'Hint about the token type', enum: ['access_token', 'refresh_token']),
        new OA\Property(property: 'client_id', type: 'string', description: 'Client identifier (if not using HTTP Basic auth)'),
        new OA\Property(property: 'client_secret', type: 'string', description: 'Client secret (if not using HTTP Basic auth)'),
    ]
)]
class OAuth2TokenRevocationRequestSchema
{
}
