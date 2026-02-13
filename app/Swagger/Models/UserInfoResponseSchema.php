<?php

namespace App\Swagger\schemas;

use OAuth2\AddressClaim;
use OAuth2\StandardClaims;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserInfoAddressClaim',
    title: 'Address Claim',
    description: 'OpenID Connect Address Claim (RFC 5.1.1)',
    type: 'object',
    properties: [
        new OA\Property(property: AddressClaim::Country, type: 'string', description: 'Country name'),
        new OA\Property(property: AddressClaim::StreetAddress, type: 'string', description: 'Full street address component'),
        new OA\Property(property: AddressClaim::Address1, type: 'string', description: 'Address line 1'),
        new OA\Property(property: AddressClaim::Address2, type: 'string', description: 'Address line 2'),
        new OA\Property(property: AddressClaim::PostalCode, type: 'string', description: 'Zip code or postal code'),
        new OA\Property(property: AddressClaim::Region, type: 'string', description: 'State, province, or region'),
        new OA\Property(property: AddressClaim::Locality, type: 'string', description: 'City or locality'),
        new OA\Property(property: AddressClaim::Formatted, type: 'string', description: 'Full mailing address, formatted for display'),
    ]
)]
class UserInfoAddressClaimSchema
{
}

#[OA\Schema(
    schema: 'UserInfoResponse',
    title: 'UserInfo Response',
    description: 'OpenID Connect UserInfo endpoint response. Claims returned depend on the requested scopes (profile, email, address).',
    type: 'object',
    required: [StandardClaims::SubjectIdentifier, 'aud'],
    properties: [
        // JWT standard claims
        new OA\Property(property: StandardClaims::SubjectIdentifier, type: 'string', description: 'Subject identifier for the End-User'),
        new OA\Property(property: 'aud', type: 'string', description: 'Audience (client ID)'),

        // Profile scope claims
        new OA\Property(property: StandardClaims::Name, type: 'string', description: 'Full name'),
        new OA\Property(property: StandardClaims::GivenName, type: 'string', description: 'First name'),
        new OA\Property(property: StandardClaims::PreferredUserName, type: 'string', description: 'Preferred username'),
        new OA\Property(property: StandardClaims::FamilyName, type: 'string', description: 'Last name'),
        new OA\Property(property: StandardClaims::NickName, type: 'string', description: 'Casual name or identifier'),
        new OA\Property(property: StandardClaims::Picture, type: 'string', format: 'uri', description: 'Profile picture URL'),
        new OA\Property(property: StandardClaims::Birthdate, type: 'string', description: 'Date of birth'),
        new OA\Property(property: StandardClaims::Gender, type: 'string', description: 'Gender'),
        new OA\Property(property: StandardClaims::GenderSpecify, type: 'string', description: 'Gender specification'),
        new OA\Property(property: StandardClaims::Locale, type: 'string', description: 'Preferred language'),
        new OA\Property(property: StandardClaims::Bio, type: 'string', description: 'User biography'),
        new OA\Property(property: StandardClaims::StatementOfInterest, type: 'string', description: 'Statement of interest'),
        new OA\Property(property: StandardClaims::Irc, type: 'string', description: 'IRC handle'),
        new OA\Property(property: StandardClaims::GitHubUser, type: 'string', description: 'GitHub username'),
        new OA\Property(property: StandardClaims::WeChatUser, type: 'string', description: 'WeChat username'),
        new OA\Property(property: StandardClaims::TwitterName, type: 'string', description: 'Twitter handle'),
        new OA\Property(property: StandardClaims::LinkedInProfile, type: 'string', description: 'LinkedIn profile URL'),
        new OA\Property(property: StandardClaims::Company, type: 'string', description: 'Company name'),
        new OA\Property(property: StandardClaims::JobTitle, type: 'string', description: 'Job title'),
        new OA\Property(property: StandardClaims::ShowPicture, type: 'boolean', description: 'Show photo in public profile'),
        new OA\Property(property: StandardClaims::ShowBio, type: 'boolean', description: 'Show bio in public profile'),
        new OA\Property(property: StandardClaims::ShowSocialMediaInfo, type: 'boolean', description: 'Show social media info in public profile'),
        new OA\Property(property: StandardClaims::ShowFullName, type: 'boolean', description: 'Show full name in public profile'),
        new OA\Property(property: StandardClaims::AllowChatWithMe, type: 'boolean', description: 'Allow chat in public profile'),
        new OA\Property(property: StandardClaims::ShowTelephoneNumber, type: 'boolean', description: 'Show telephone in public profile'),
        new OA\Property(
            property: StandardClaims::Groups,
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Group'),
            description: 'User groups'
        ),

        // Email scope claims
        new OA\Property(property: StandardClaims::Email, type: 'string', format: 'email', description: 'Primary email address'),
        new OA\Property(property: StandardClaims::SecondEmail, type: 'string', format: 'email', description: 'Secondary email address'),
        new OA\Property(property: StandardClaims::ThirdEmail, type: 'string', format: 'email', description: 'Tertiary email address'),
        new OA\Property(property: StandardClaims::EmailVerified, type: 'boolean', description: 'Whether the primary email is verified'),
        new OA\Property(property: StandardClaims::ShowEmail, type: 'boolean', description: 'Whether to show the email or not'),

        // Address scope claims
        new OA\Property(
            property: StandardClaims::Address,
            ref: '#/components/schemas/UserInfoAddressClaim',
            description: 'End-User preferred postal address (address scope)'
        ),
    ]
)]
class UserInfoResponseSchema
{
}
