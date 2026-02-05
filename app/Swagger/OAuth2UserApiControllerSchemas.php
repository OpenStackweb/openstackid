<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateUser',
    title: 'Create User',
    description: 'Request body for creating a new user',
    required: ['email'],
    type: 'object',
    properties: [
        new OA\Property(property: 'first_name', type: 'string', description: 'First name'),
        new OA\Property(property: 'last_name', type: 'string', description: 'Last name'),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Primary email address'),
        new OA\Property(property: 'identifier', type: 'string', description: 'User unique identifier string'),
        new OA\Property(property: 'bio', type: 'string', nullable: true, description: 'User biography'),
        new OA\Property(property: 'address1', type: 'string', nullable: true, description: 'Address line 1'),
        new OA\Property(property: 'address2', type: 'string', nullable: true, description: 'Address line 2'),
        new OA\Property(property: 'city', type: 'string', nullable: true, description: 'City'),
        new OA\Property(property: 'state', type: 'string', nullable: true, description: 'State or province'),
        new OA\Property(property: 'post_code', type: 'string', nullable: true, description: 'Postal code'),
        new OA\Property(property: 'country_iso_code', type: 'string', nullable: true, description: 'ISO 3166-1 alpha-2 country code'),
        new OA\Property(property: 'second_email', type: 'string', format: 'email', nullable: true, description: 'Secondary email address'),
        new OA\Property(property: 'third_email', type: 'string', format: 'email', nullable: true, description: 'Tertiary email address'),
        new OA\Property(property: 'gender', type: 'string', nullable: true, description: 'Gender'),
        new OA\Property(property: 'statement_of_interest', type: 'string', nullable: true, description: 'Statement of interest'),
        new OA\Property(property: 'irc', type: 'string', nullable: true, description: 'IRC handle'),
        new OA\Property(property: 'linked_in_profile', type: 'string', nullable: true, description: 'LinkedIn profile URL'),
        new OA\Property(property: 'github_user', type: 'string', nullable: true, description: 'GitHub username'),
        new OA\Property(property: 'wechat_user', type: 'string', nullable: true, description: 'WeChat username'),
        new OA\Property(property: 'twitter_name', type: 'string', nullable: true, description: 'Twitter handle'),
        new OA\Property(property: 'language', type: 'string', nullable: true, description: 'Preferred language'),
        new OA\Property(property: 'birthday', type: 'integer', nullable: true, description: 'Date of birth (epoch)'),
        new OA\Property(property: 'password', type: 'string', format: 'password', description: 'Password'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', description: 'Password confirmation (required when password is provided)'),
        new OA\Property(property: 'phone_number', type: 'string', nullable: true, description: 'Phone number'),
        new OA\Property(property: 'company', type: 'string', nullable: true, description: 'Company name'),
        new OA\Property(property: 'job_title', type: 'string', nullable: true, maxLength: 200, description: 'Job title'),
        new OA\Property(property: 'email_verified', type: 'boolean', nullable: true, description: 'Whether the primary email is verified (admin only)'),
        new OA\Property(property: 'active', type: 'boolean', nullable: true, description: 'Whether the user account is active (admin only)'),
        new OA\Property(property: 'groups', type: 'array', items: new OA\Items(type: 'integer'), description: 'Group IDs to assign (admin only)'),
        new OA\Property(property: 'public_profile_show_photo', type: 'boolean', description: 'Show photo in public profile'),
        new OA\Property(property: 'public_profile_show_fullname', type: 'boolean', description: 'Show full name in public profile'),
        new OA\Property(property: 'public_profile_show_email', type: 'boolean', description: 'Show email in public profile'),
        new OA\Property(property: 'public_profile_show_social_media_info', type: 'boolean', description: 'Show social media info in public profile'),
        new OA\Property(property: 'public_profile_show_bio', type: 'boolean', description: 'Show bio in public profile'),
        new OA\Property(property: 'public_profile_allow_chat_with_me', type: 'boolean', description: 'Allow chat in public profile'),
        new OA\Property(property: 'public_profile_show_telephone_number', type: 'boolean', description: 'Show telephone in public profile'),
    ]
)]
class CreateUserSchema
{
}

#[OA\Schema(
    schema: 'UpdateUser',
    title: 'Update User',
    description: 'Request body for updating a user',
    type: 'object',
    properties: [
        new OA\Property(property: 'first_name', type: 'string', description: 'First name'),
        new OA\Property(property: 'last_name', type: 'string', description: 'Last name'),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Primary email address'),
        new OA\Property(property: 'identifier', type: 'string', description: 'User unique identifier string'),
        new OA\Property(property: 'bio', type: 'string', nullable: true, description: 'User biography'),
        new OA\Property(property: 'address1', type: 'string', nullable: true, description: 'Address line 1'),
        new OA\Property(property: 'address2', type: 'string', nullable: true, description: 'Address line 2'),
        new OA\Property(property: 'city', type: 'string', nullable: true, description: 'City'),
        new OA\Property(property: 'state', type: 'string', nullable: true, description: 'State or province'),
        new OA\Property(property: 'post_code', type: 'string', nullable: true, description: 'Postal code'),
        new OA\Property(property: 'country_iso_code', type: 'string', nullable: true, description: 'ISO 3166-1 alpha-2 country code'),
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
        new OA\Property(property: 'password', type: 'string', format: 'password', description: 'New password'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', description: 'Password confirmation (required when password is provided)'),
        new OA\Property(property: 'current_password', type: 'string', format: 'password', description: 'Current password (required when changing password for non-admin users)'),
        new OA\Property(property: 'phone_number', type: 'string', nullable: true, description: 'Phone number'),
        new OA\Property(property: 'company', type: 'string', nullable: true, description: 'Company name'),
        new OA\Property(property: 'job_title', type: 'string', nullable: true, maxLength: 200, description: 'Job title'),
        new OA\Property(property: 'email_verified', type: 'boolean', nullable: true, description: 'Whether the primary email is verified (admin only)'),
        new OA\Property(property: 'active', type: 'boolean', nullable: true, description: 'Whether the user account is active (admin only)'),
        new OA\Property(property: 'groups', type: 'array', items: new OA\Items(type: 'integer'), description: 'Group IDs to assign (admin only)'),
        new OA\Property(property: 'public_profile_show_photo', type: 'boolean', description: 'Show photo in public profile'),
        new OA\Property(property: 'public_profile_show_fullname', type: 'boolean', description: 'Show full name in public profile'),
        new OA\Property(property: 'public_profile_show_email', type: 'boolean', description: 'Show email in public profile'),
        new OA\Property(property: 'public_profile_show_social_media_info', type: 'boolean', description: 'Show social media info in public profile'),
        new OA\Property(property: 'public_profile_show_bio', type: 'boolean', description: 'Show bio in public profile'),
        new OA\Property(property: 'public_profile_allow_chat_with_me', type: 'boolean', description: 'Allow chat in public profile'),
        new OA\Property(property: 'public_profile_show_telephone_number', type: 'boolean', description: 'Show telephone in public profile'),
    ]
)]
class UpdateUserSchema
{
}

#[OA\Schema(
    schema: 'UpdateUserPic',
    title: 'Update User Profile Picture',
    description: 'Request body for updating user profile picture',
    required: ['file'],
    type: 'object',
    properties: [
        new OA\Property(
            property: 'file',
            type: 'string',
            format: 'binary',
            description: 'Profile picture file'
        ),
    ]
)]
class UpdateUserPicSchema
{
}

#[OA\Schema(
    schema: 'UpdateUserGroups',
    title: 'Update User Groups',
    description: 'Request body for updating user group assignments',
    required: ['groups'],
    type: 'object',
    properties: [
        new OA\Property(
            property: 'groups',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of group IDs to assign to the user'
        ),
    ]
)]
class UpdateUserGroupsSchema
{
}
