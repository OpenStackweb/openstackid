<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OAuth2TokenResponse',
    title: 'OAuth2 Token Response',
    description: 'Successful token response per RFC 6749 §5.1',
    type: 'object',
    properties: [
        new OA\Property(property: 'access_token', type: 'string', description: 'The access token issued by the authorization server'),
        new OA\Property(property: 'token_type', type: 'string', description: 'The type of the token (typically Bearer)', example: 'Bearer'),
        new OA\Property(property: 'expires_in', type: 'integer', description: 'Lifetime of the access token in seconds', example: 3600),
        new OA\Property(property: 'refresh_token', type: 'string', description: 'Refresh token (only if access_type=offline)', nullable: true),
        new OA\Property(property: 'scope', type: 'string', description: 'Space-delimited list of granted scopes'),
        new OA\Property(property: 'id_token', type: 'string', description: 'ID token JWT (only for OpenID Connect flows with openid scope)', nullable: true),
    ]
)]
class OAuth2TokenResponseSchema
{
}

#[OA\Schema(
    schema: 'OAuth2ErrorResponse',
    title: 'OAuth2 Error Response',
    description: 'Error response per RFC 6749 §5.2',
    type: 'object',
    required: ['error'],
    properties: [
        new OA\Property(property: 'error', type: 'string', description: 'Error code', example: 'invalid_request'),
        new OA\Property(property: 'error_description', type: 'string', description: 'Human-readable error description'),
    ]
)]
class OAuth2ErrorResponseSchema
{
}

#[OA\Schema(
    schema: 'OAuth2IntrospectionResponse',
    title: 'OAuth2 Token Introspection Response',
    description: 'Token introspection response per RFC 7662',
    type: 'object',
    properties: [
        new OA\Property(property: 'active', type: 'boolean', description: 'Whether the token is active'),
        new OA\Property(property: 'access_token', type: 'string', description: 'The access token value'),
        new OA\Property(property: 'client_id', type: 'string', description: 'Client identifier'),
        new OA\Property(property: 'application_type', type: 'string', description: 'Client application type', enum: ['WEB', 'NATIVE', 'JS']),
        new OA\Property(property: 'token_type', type: 'string', description: 'Token type', example: 'Bearer'),
        new OA\Property(property: 'scope', type: 'string', description: 'Space-delimited scopes'),
        new OA\Property(property: 'audience', type: 'string', description: 'Token audience'),
        new OA\Property(property: 'expires_in', type: 'integer', description: 'Remaining lifetime in seconds'),
        new OA\Property(property: 'user_id', type: 'integer', description: 'Resource owner user ID', nullable: true),
        new OA\Property(property: 'user_identifier', type: 'string', description: 'Resource owner identifier', nullable: true),
        new OA\Property(property: 'user_email', type: 'string', description: 'Resource owner email', nullable: true),
        new OA\Property(property: 'user_first_name', type: 'string', description: 'Resource owner first name', nullable: true),
        new OA\Property(property: 'user_last_name', type: 'string', description: 'Resource owner last name', nullable: true),
        new OA\Property(property: 'user_pic', type: 'string', format: 'uri', description: 'Resource owner profile picture URL', nullable: true),
        new OA\Property(
            property: 'user_groups',
            type: 'array',
            description: 'Resource owner group memberships',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                ]
            ),
            nullable: true
        ),
        new OA\Property(property: 'user_email_verified', type: 'boolean', description: 'Whether the user email is verified', nullable: true),
        new OA\Property(property: 'user_language', type: 'string', description: 'User preferred language', nullable: true),
        new OA\Property(property: 'user_country', type: 'string', description: 'User country', nullable: true),
        new OA\Property(property: 'allowed_return_uris', type: 'string', description: 'Space-delimited allowed return URIs'),
        new OA\Property(property: 'allowed_origins', type: 'string', description: 'Space-delimited allowed origins'),
    ]
)]
class OAuth2IntrospectionResponseSchema
{
}

#[OA\Schema(
    schema: 'JWKSResponse',
    title: 'JSON Web Key Set',
    description: 'JWK Set document per RFC 7517',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'keys',
            type: 'array',
            description: 'Array of JSON Web Keys',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'kty', type: 'string', description: 'Key type', example: 'RSA'),
                    new OA\Property(property: 'kid', type: 'string', description: 'Key ID'),
                    new OA\Property(property: 'use', type: 'string', description: 'Key usage', example: 'sig'),
                    new OA\Property(property: 'alg', type: 'string', description: 'Algorithm', example: 'RS256'),
                    new OA\Property(property: 'n', type: 'string', description: 'RSA modulus (Base64urlUInt-encoded)'),
                    new OA\Property(property: 'e', type: 'string', description: 'RSA exponent (Base64urlUInt-encoded)', example: 'AQAB'),
                ]
            )
        ),
    ]
)]
class JWKSResponseSchema
{
}

#[OA\Schema(
    schema: 'OpenIDDiscoveryResponse',
    title: 'OpenID Connect Discovery Document',
    description: 'OpenID Provider Configuration per OpenID Connect Discovery 1.0',
    type: 'object',
    properties: [
        new OA\Property(property: 'issuer', type: 'string', format: 'uri', description: 'Issuer identifier URL'),
        new OA\Property(property: 'authorization_endpoint', type: 'string', format: 'uri', description: 'Authorization endpoint URL'),
        new OA\Property(property: 'token_endpoint', type: 'string', format: 'uri', description: 'Token endpoint URL'),
        new OA\Property(property: 'userinfo_endpoint', type: 'string', format: 'uri', description: 'UserInfo endpoint URL'),
        new OA\Property(property: 'revocation_endpoint', type: 'string', format: 'uri', description: 'Token revocation endpoint URL'),
        new OA\Property(property: 'introspection_endpoint', type: 'string', format: 'uri', description: 'Token introspection endpoint URL'),
        new OA\Property(property: 'jwks_uri', type: 'string', format: 'uri', description: 'JSON Web Key Set URL'),
        new OA\Property(
            property: 'scopes_supported',
            type: 'array',
            description: 'Supported OAuth2 scopes',
            items: new OA\Items(type: 'string')
        ),
        new OA\Property(
            property: 'response_types_supported',
            type: 'array',
            description: 'Supported response types',
            items: new OA\Items(type: 'string')
        ),
        new OA\Property(
            property: 'response_modes_supported',
            type: 'array',
            description: 'Supported response modes',
            items: new OA\Items(type: 'string')
        ),
        new OA\Property(
            property: 'grant_types_supported',
            type: 'array',
            description: 'Supported grant types',
            items: new OA\Items(type: 'string')
        ),
        new OA\Property(
            property: 'subject_types_supported',
            type: 'array',
            description: 'Supported subject types',
            items: new OA\Items(type: 'string')
        ),
        new OA\Property(
            property: 'id_token_signing_alg_values_supported',
            type: 'array',
            description: 'Supported ID token signing algorithms',
            items: new OA\Items(type: 'string')
        ),
        new OA\Property(
            property: 'code_challenge_methods_supported',
            type: 'array',
            description: 'Supported PKCE code challenge methods',
            items: new OA\Items(type: 'string')
        ),
    ]
)]
class OpenIDDiscoveryResponseSchema
{
}
