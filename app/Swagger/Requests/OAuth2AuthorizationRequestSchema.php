<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OAuth2AuthorizationRequest',
    title: 'OAuth2 Authorization Request',
    description: 'Authorization request parameters per RFC 6749 §4.1.1 and OpenID Connect Core §3.1.2.1',
    type: 'object',
    required: ['response_type', 'client_id', 'redirect_uri'],
    properties: [
        new OA\Property(property: 'response_type', type: 'string', description: 'OAuth2 response type', enum: ['code', 'token', 'id_token', 'code token', 'code id_token', 'token id_token', 'code token id_token', 'otp', 'none']),
        new OA\Property(property: 'client_id', type: 'string', description: 'OAuth2 client identifier'),
        new OA\Property(property: 'redirect_uri', type: 'string', format: 'uri', description: 'Redirect URI'),
        new OA\Property(property: 'scope', type: 'string', description: 'Space-delimited scopes'),
        new OA\Property(property: 'state', type: 'string', description: 'Opaque state parameter'),
        new OA\Property(property: 'nonce', type: 'string', description: 'Nonce for ID token replay protection'),
        new OA\Property(property: 'response_mode', type: 'string', description: 'Response mode override', enum: ['query', 'fragment', 'form_post', 'direct']),
        new OA\Property(property: 'prompt', type: 'string', description: 'User interaction prompts'),
        new OA\Property(property: 'login_hint', type: 'string', description: 'Login identifier hint'),
        new OA\Property(property: 'code_challenge', type: 'string', description: 'PKCE code challenge'),
        new OA\Property(property: 'code_challenge_method', type: 'string', description: 'PKCE challenge method', enum: ['plain', 'S256']),
    ]
)]
class OAuth2AuthorizationRequestSchema
{
}
