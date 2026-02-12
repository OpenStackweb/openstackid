<?php

namespace App\Swagger\schemas;

use App\libs\OAuth2\IUserScopes;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    securityScheme: 'OAuth2StreamChatSSOSecurity',
    type: 'oauth2',
    description: 'OAuth2 authentication for Stream Chat SSO endpoints',
    flows: [
        new OA\Flow(
            flow: 'authorizationCode',
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            scopes: [IUserScopes::SSO => 'Single Sign-On access']
        ),
    ]
)]
class OAuth2StreamChatSSOApiControllerSecuritySchema
{
}