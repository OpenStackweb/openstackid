<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OAuth2EndSessionRequest',
    title: 'OAuth2 End Session Request',
    description: 'RP-Initiated Logout request parameters per OpenID Connect Session Management 1.0',
    type: 'object',
    required: ['client_id'],
    properties: [
        new OA\Property(property: 'client_id', type: 'string', description: 'OAuth2 client identifier'),
        new OA\Property(property: 'id_token_hint', type: 'string', description: 'Previously issued ID token'),
        new OA\Property(property: 'post_logout_redirect_uri', type: 'string', format: 'uri', description: 'URI to redirect after logout'),
        new OA\Property(property: 'state', type: 'string', description: 'Opaque state parameter'),
    ]
)]
class OAuth2EndSessionRequestSchema
{
}
