<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    title: 'User',
    description: 'User serialized representation (private)',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/BaseUser'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Primary email address'),
                new OA\Property(property: 'identifier', type: 'string', description: 'User unique identifier string'),
                new OA\Property(property: 'email_verified', type: 'boolean', description: 'Whether the primary email is verified'),
                new OA\Property(property: 'bio', type: 'string', nullable: true, description: 'User biography'),
                new OA\Property(property: 'address1', type: 'string', description: 'Address line 1'),
                new OA\Property(property: 'address2', type: 'string', nullable: true, description: 'Address line 2'),
                new OA\Property(property: 'city', type: 'string', description: 'City'),
                new OA\Property(property: 'state', type: 'string', description: 'State or province'),
                new OA\Property(property: 'post_code', type: 'string', description: 'Postal code'),
                new OA\Property(property: 'country_iso_code', type: 'string', description: 'ISO country code'),
                new OA\Property(property: 'second_email', type: 'string', format: 'email', nullable: true, description: 'Secondary email address'),
                new OA\Property(property: 'third_email', type: 'string', format: 'email', nullable: true, description: 'Tertiary email address'),
                new OA\Property(property: 'gender', type: 'string', nullable: true, description: 'Gender'),
                new OA\Property(property: 'gender_specify', type: 'string', nullable: true, description: 'Gender specification'),
                new OA\Property(property: 'statement_of_interest', type: 'string', nullable: true, description: 'Statement of interest'),
                new OA\Property(property: 'irc', type: 'string', nullable: true, description: 'IRC handle'),
                new OA\Property(property: 'linked_in_profile', type: 'string', nullable: true, description: 'LinkedIn profile URL'),
                new OA\Property(property: 'github_user', type: 'string', nullable: true, description: 'GitHub username'),
                new OA\Property(property: 'wechat_user', type: 'string', nullable: true, description: 'WeChat username'),
                new OA\Property(property: 'twitter_name', type: 'string', nullable: true, description: 'Twitter handle'),
                new OA\Property(property: 'language', type: 'string', nullable: true, description: 'Preferred language'),
                new OA\Property(property: 'birthday', type: 'integer', nullable: true, description: 'Date of birth (epoch)'),
                new OA\Property(property: 'phone_number', type: 'string', nullable: true, description: 'Phone number'),
                new OA\Property(property: 'company', type: 'string', nullable: true, description: 'Company name'),
                new OA\Property(property: 'job_title', type: 'string', nullable: true, description: 'Job title'),
                new OA\Property(property: 'spam_type', type: 'string', description: 'Spam classification', enum: ['None', 'Spam', 'Ham']),
                new OA\Property(property: 'last_login_date', type: 'integer', nullable: true, description: 'Last login date (epoch)'),
                new OA\Property(property: 'active', type: 'boolean', description: 'Whether the user account is active'),
                new OA\Property(property: 'public_profile_show_photo', type: 'boolean', description: 'Show photo in public profile'),
                new OA\Property(property: 'public_profile_show_fullname', type: 'boolean', description: 'Show full name in public profile'),
                new OA\Property(property: 'public_profile_show_email', type: 'boolean', description: 'Show email in public profile'),
                new OA\Property(property: 'public_profile_show_social_media_info', type: 'boolean', description: 'Show social media info in public profile'),
                new OA\Property(property: 'public_profile_show_bio', type: 'boolean', description: 'Show bio in public profile'),
                new OA\Property(property: 'public_profile_allow_chat_with_me', type: 'boolean', description: 'Allow chat in public profile'),
                new OA\Property(property: 'public_profile_show_telephone_number', type: 'boolean', description: 'Show telephone in public profile'),
                new OA\Property(
                    property: 'groups',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Group'),
                    description: 'User groups (expandable with expand=groups)'
                ),
            ]
        )
    ]
)]
class UserSchema
{
}
