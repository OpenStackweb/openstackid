<?php

namespace App\Swagger\schemas;

use App\libs\OAuth2\IUserScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'user_oauth2',
    flows: [
        new OA\Flow(
            flow: 'authorizationCode',
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            scopes: [
                IUserScopes::Profile => 'Read User Profile',
                IUserScopes::Email => 'Read User Email',
                IUserScopes::Address => 'Read User Address',
                IUserScopes::ReadAll => 'Read All Users Data',
                IUserScopes::MeWrite => 'Write Current User Data',
                IUserScopes::Write => 'Write Users Data',
                IUserScopes::UserGroupWrite => 'Write User Group Assignments',
            ],
        ),
    ],
)
]
class UsersOAuth2Schema
{
}
