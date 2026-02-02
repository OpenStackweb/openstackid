<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "OpenStackID API",
    description: "OpenStackID IDP API Documentation - OAuth2, OpenID Connect, and User Management",
    contact: new OA\Contact(
        name: "OpenStack Foundation",
        email: "support@openstack.org"
    ),
    license: new OA\License(
        name: "MIT",
        url: "https://opensource.org/licenses/MIT"
    )
)]
#[OA\Server(
    url: "/",
    description: "IDP API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Tag(name: "Users", description: "User management endpoints")]
#[OA\Tag(name: "Groups", description: "Group management endpoints")]
class OpenApiInfo
{
}

