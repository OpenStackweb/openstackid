<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "IDP API",
    description: "IDP API Documentation - OAuth2, OpenID Connect, and User Management",
    contact: new OA\Contact(
        name: "FNTECH",
        email: "support@fntech.com"
    ),
    license: new OA\License(
        name: "Apache 2.0",
        url: "http://www.apache.org/licenses/LICENSE-2.0"
    )
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: "IDP API Server"
)]
class OpenApiInfo
{
}