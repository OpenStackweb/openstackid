<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DisqusUserProfileSerialized',
    type: 'object',
    properties: [
        new OA\Property(property: 'auth', type: 'string', description: 'Base64 encoded profile JSON + space + hash + space + timestamp.'),
        new OA\Property(property: 'public_key', type: 'string', description: 'Public key'),
    ],
    description: 'Disqus SSO user profile'
)]
class DisqusUserProfileSerializedSchema
{
}
