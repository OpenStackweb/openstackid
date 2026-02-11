<?php

namespace App\Swagger\schemas;

use App\libs\OAuth2\IGroupScopes;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    securityScheme: 'OAuth2GroupsSecurity',
    type: 'oauth2',
    description: 'OAuth2 authentication for Group endpoints',
    flows: [
        new OA\Flow(
            flow: 'authorizationCode',
            authorizationUrl: '/oauth2/auth',
            tokenUrl: '/oauth2/token',
            scopes: [IGroupScopes::ReadAll => 'Read all groups']
        ),
    ]
)]
class OAuth2GroupApiControllerSecuritySchema
{
}
