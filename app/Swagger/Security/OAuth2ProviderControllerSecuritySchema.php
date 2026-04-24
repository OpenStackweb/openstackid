<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    securityScheme: 'OAuth2ProviderSecurity',
    type: 'oauth2',
    description: 'OAuth2 client credentials authentication for protocol endpoints (token, revoke, introspection)',
    flows: [
        new OA\Flow(
            flow: 'clientCredentials',
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            scopes: []
        ),
    ]
)]
class OAuth2ProviderControllerSecuritySchema
{
}