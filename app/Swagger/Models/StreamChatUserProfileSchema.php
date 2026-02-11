<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StreamChatUserProfile',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', description: 'User ID'),
        new OA\Property(property: 'name', type: 'string', description: 'Display name'),
        new OA\Property(property: 'image', type: 'string', description: 'Avatar URL'),
        new OA\Property(property: 'token', type: 'string', description: 'Stream Chat JWT token'),
        new OA\Property(property: 'api_key', type: 'string', description: 'Stream Chat API key'),
        new OA\Property(property: 'local_role', type: 'string', description: 'User role in the forum'),
    ],
    description: 'Stream Chat SSO user profile'
)]
class StreamChatUserProfileSchema
{
}
