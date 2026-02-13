<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RocketChatUserProfile',
    type: 'object',
    properties: [
    ],
    description: 'Rocket Chat SSO user profile'
)]
class RocketChatUserProfileSchema
{
}
