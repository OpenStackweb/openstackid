<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OAuth2TokenRequest',
    title: 'OAuth2 Token Request',
    description: 'Token request parameters per RFC 6749 §4.1.3. Fields required vary by grant_type.',
    type: 'object',
    required: ['grant_type'],
    properties: [
        new OA\Property(property: 'grant_type', type: 'string', description: 'OAuth2 grant type', enum: ['authorization_code', 'client_credentials', 'password', 'refresh_token', 'passwordless']),
        new OA\Property(property: 'code', type: 'string', description: 'Authorization code (authorization_code grant)'),
        new OA\Property(property: 'redirect_uri', type: 'string', format: 'uri', description: 'Redirect URI (must match the one used in authorization request)'),
        new OA\Property(property: 'client_id', type: 'string', description: 'Client identifier (if not using HTTP Basic auth)'),
        new OA\Property(property: 'client_secret', type: 'string', description: 'Client secret (if not using HTTP Basic auth)'),
        new OA\Property(property: 'refresh_token', type: 'string', description: 'Refresh token (refresh_token grant)'),
        new OA\Property(property: 'scope', type: 'string', description: 'Space-delimited scopes'),
        new OA\Property(property: 'username', type: 'string', description: 'Username (password grant)'),
        new OA\Property(property: 'password', type: 'string', description: 'Password (password grant)'),
        new OA\Property(property: 'audience', type: 'string', description: 'Target audience (client_credentials grant)'),
        new OA\Property(property: 'connection', type: 'string', description: 'Connection type (passwordless grant)', enum: ['sms', 'email', 'inline']),
        new OA\Property(property: 'send', type: 'string', description: 'Delivery method (passwordless grant)', enum: ['code', 'link']),
        new OA\Property(property: 'email', type: 'string', description: 'Email address (passwordless grant)'),
        new OA\Property(property: 'phone_number', type: 'string', description: 'Phone number (passwordless grant)'),
    ]
)]
class OAuth2TokenRequestSchema
{
}
