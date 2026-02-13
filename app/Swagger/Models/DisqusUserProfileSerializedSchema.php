<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DisqusUserProfileSerialized',
    type: 'object',
    properties: [
        new OA\Property(property: 'auth', type: 'string', description: 'string of base64 profile JSON + space + hash + space + timestamp. The base64 encoded profile is a JSON stringfied object containing the user profile information: id, username, email, avatar in that order. The hash is the base64 encoded info signed with public/private keys'),
        new OA\Property(property: 'public_key', type: 'string', description: 'Public key'),
    ],
    description: 'Disqus SSO user profile'
)]
class DisqusUserProfileSerializedSchema
{
}
