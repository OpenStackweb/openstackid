<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OAuth2AuthorizationRequest',
    title: 'OAuth2 Authorization Request',
    description: 'Authorization request parameters per RFC 6749 §4.1.1 and OpenID Connect Core §3.1.2.1',
    type: 'object',
    required: ['response_type', 'client_id', 'redirect_uri'],
    properties: [
        new OA\Property(property: 'response_type', type: 'string', description: 'The OAuth 2.0 specification allows for registration of space-separated response_type parameter values. If a Response Type contains one of more space characters (%20), it is compared as a space-delimited list of values in which the order of values does not matter. Possible values are: code, token, id_token, otp, none. The "none" value cannot be used with any other response type value.'),
        new OA\Property(property: 'client_id', type: 'string', description: 'OAuth2 client identifier'),
        new OA\Property(property: 'redirect_uri', type: 'string', format: 'uri', description: 'Redirect URI'),
        new OA\Property(property: 'scope', type: 'string', description: 'Space-delimited scopes'),
        new OA\Property(property: 'state', type: 'string', description: 'Opaque state parameter'),
        new OA\Property(property: 'approval_prompt', type: 'string', description: 'Indicates whether the user should be re-prompted for consent. The default is auto, so a given user should only see the consent page for a given set of scopes the first time through the sequence. If the value is force, then the user sees a consent page even if they previously gave consent to your application for a given set of scopes.', enum: ['auto', 'force']),
        new OA\Property(property: 'access_type', type: 'string', description: 'Indicates whether your application needs to access an API when the user is not present at the browser. This parameter defaults to online. If your application needs to refresh access tokens when the user is not present at the browser, then use offline. This will result in your application obtaining a refresh token the first time your application exchanges an authorization code for a user.', enum: ['online', 'offline']),
        new OA\Property(property: 'response_mode', type: 'string', description: 'OPTIONAL. Informs the Authorization Server of the mechanism to be used for returning Authorization Response parameters from the Authorization Endpoint. This use of this parameter is NOT RECOMMENDED with a value that specifies the same Response Mode as the default Response Mode for the Response Type used.\nThe default Response Mode for the OAuth 2.0 code Response Type is the query encoding. For purposes of this specification, the default Response Mode for the OAuth 2.0 token Response Type is the fragment encoding.', enum: ['query', 'fragment', 'form_post', 'direct']),
        new OA\Property(property: 'code_challenge', type: 'string', description: 'PKCE code challenge'),
        new OA\Property(property: 'code_challenge_method', type: 'string', description: 'Optional. PKCE challenge method', enum: ['plain', 'S256']),
        new OA\Property(property: 'display', type: 'string', description: 'UI display preference (OIDC)', enum: ['page', 'popup', 'touch', 'wap', 'native']),
        new OA\Property(property: 'tenant', type: 'string', description: 'Tenant identifier'),
    ]
)]
class OAuth2AuthorizationRequestSchema
{
}