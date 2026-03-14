<?php

namespace App\Swagger\schemas;

use App\libs\OAuth2\IUserScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'OAuth2UserSecurity',
    description: 'OAuth2 security scheme for user-related API endpoints',
    flows: [
        new OA\Flow(
            flow: 'authorizationCode',
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            scopes: [
                IUserScopes::ReadAll => 'Read All Users Data',
                IUserScopes::MeWrite => 'Write current user data',
                IUserScopes::Write => 'Write Users Data',
                IUserScopes::UserGroupWrite => 'Manage User Group assignments',
            ],
        ),
    ],
)
]
class OAuth2UserApiControllerSecuritySchema
{
}
