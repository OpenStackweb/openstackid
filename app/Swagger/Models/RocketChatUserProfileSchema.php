<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RocketChatUserProfile',
    type: 'object',
    additionalProperties: true,
    description: 'Rocket Chat SSO user profile. The response structure is the "data" portion of the Rocket Chat /api/v1/login endpoint response and is defined by the external Rocket Chat server.'
)]
class RocketChatUserProfileSchema
{
}