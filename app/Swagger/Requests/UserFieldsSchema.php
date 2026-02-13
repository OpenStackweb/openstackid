<?php namespace App\Swagger\schemas;

/**
 * Copyright 2025 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserFields',
    title: 'User Fields',
    description: 'Common user fields used across user operations',
    type: 'object',
    properties: [
        // Personal Information
        new OA\Property(
            property: 'first_name',
            type: 'string',
            description: 'User first name',
            nullable: true,
            example: 'John'
        ),
        new OA\Property(
            property: 'last_name',
            type: 'string',
            description: 'User last name',
            nullable: true,
            example: 'Doe'
        ),
        new OA\Property(
            property: 'gender',
            type: 'string',
            description: 'User gender',
            nullable: true,
            example: 'Male'
        ),
        new OA\Property(
            property: 'gender_specify',
            type: 'string',
            description: 'Custom gender specification',
            nullable: true
        ),
        new OA\Property(
            property: 'birthday',
            type: 'integer',
            description: 'Birthday as Unix timestamp (seconds since epoch)',
            nullable: true,
            example: 631152000
        ),
        new OA\Property(
            property: 'language',
            type: 'string',
            description: 'Preferred language',
            nullable: true,
            example: 'en'
        ),

        // Contact Information
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            description: 'Primary email address',
            example: 'john.doe@example.com'
        ),
        new OA\Property(
            property: 'second_email',
            type: 'string',
            format: 'email',
            description: 'Secondary email address',
            nullable: true,
            example: 'john.work@example.com'
        ),
        new OA\Property(
            property: 'third_email',
            type: 'string',
            format: 'email',
            description: 'Tertiary email address',
            nullable: true,
            example: 'john.alt@example.com'
        ),
        new OA\Property(
            property: 'phone_number',
            type: 'string',
            description: 'Phone number',
            nullable: true,
            example: '+1-555-0123'
        ),

        // Address Fields
        new OA\Property(
            property: 'address1',
            type: 'string',
            description: 'Address line 1',
            nullable: true,
            example: '123 Main Street'
        ),
        new OA\Property(
            property: 'address2',
            type: 'string',
            description: 'Address line 2',
            nullable: true,
            example: 'Apt 4B'
        ),
        new OA\Property(
            property: 'city',
            type: 'string',
            description: 'City',
            nullable: true,
            example: 'San Francisco'
        ),
        new OA\Property(
            property: 'state',
            type: 'string',
            description: 'State or province',
            nullable: true,
            example: 'CA'
        ),
        new OA\Property(
            property: 'post_code',
            type: 'string',
            description: 'Postal code',
            nullable: true,
            example: '94102'
        ),
        new OA\Property(
            property: 'country_iso_code',
            type: 'string',
            description: 'ISO 3166-1 alpha-2 country code',
            nullable: true,
            example: 'US'
        ),

        // Professional Information
        new OA\Property(
            property: 'company',
            type: 'string',
            description: 'Company name',
            nullable: true,
            example: 'Acme Corp'
        ),
        new OA\Property(
            property: 'job_title',
            type: 'string',
            description: 'Job title (max 200 characters)',
            nullable: true,
            maxLength: 200,
            example: 'Software Engineer'
        ),
        new OA\Property(
            property: 'bio',
            type: 'string',
            description: 'User biography (HTML content will be sanitized)',
            nullable: true,
            example: 'Passionate developer with 10 years of experience'
        ),
        new OA\Property(
            property: 'statement_of_interest',
            type: 'string',
            description: 'Statement of interest (HTML content will be sanitized)',
            nullable: true,
            example: 'Interested in cloud computing and open source'
        ),

        // Social Media
        new OA\Property(
            property: 'irc',
            type: 'string',
            description: 'IRC nickname',
            nullable: true,
            example: 'johndoe'
        ),
        new OA\Property(
            property: 'twitter_name',
            type: 'string',
            description: 'Twitter username',
            nullable: true,
            example: '@johndoe'
        ),
        new OA\Property(
            property: 'linked_in_profile',
            type: 'string',
            description: 'LinkedIn profile URL',
            nullable: true,
            example: 'https://linkedin.com/in/johndoe'
        ),
        new OA\Property(
            property: 'github_user',
            type: 'string',
            description: 'GitHub username',
            nullable: true,
            example: 'johndoe'
        ),
        new OA\Property(
            property: 'wechat_user',
            type: 'string',
            description: 'WeChat username',
            nullable: true,
            example: 'johndoe'
        ),

        // Public Profile Settings
        new OA\Property(
            property: 'public_profile_show_photo',
            type: 'boolean',
            description: 'Show photo in public profile',
            example: true
        ),
        new OA\Property(
            property: 'public_profile_show_fullname',
            type: 'boolean',
            description: 'Show full name in public profile',
            example: true
        ),
        new OA\Property(
            property: 'public_profile_show_email',
            type: 'boolean',
            description: 'Show email in public profile',
            example: false
        ),
        new OA\Property(
            property: 'public_profile_show_social_media_info',
            type: 'boolean',
            description: 'Show social media information in public profile',
            example: true
        ),
        new OA\Property(
            property: 'public_profile_show_bio',
            type: 'boolean',
            description: 'Show biography in public profile',
            example: true
        ),
        new OA\Property(
            property: 'public_profile_allow_chat_with_me',
            type: 'boolean',
            description: 'Allow others to chat with me',
            example: true
        ),
        new OA\Property(
            property: 'public_profile_show_telephone_number',
            type: 'boolean',
            description: 'Show telephone number in public profile',
            example: false
        ),

        // Security
        new OA\Property(
            property: 'password',
            type: 'string',
            description: 'Password (must meet password policy requirements)',
            example: 'SecureP@ssw0rd'
        ),
        new OA\Property(
            property: 'password_confirmation',
            type: 'string',
            description: 'Password confirmation (required when password is provided)',
            example: 'SecureP@ssw0rd'
        ),
        new OA\Property(
            property: 'current_password',
            type: 'string',
            description: 'Current password (required when changing password for non-admin users)',
            example: 'OldP@ssw0rd'
        ),

        // Admin Fields (accepted on create; automatically removed on update for non-admin users by curateUpdatePayload)
        new OA\Property(
            property: 'groups',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of group IDs to assign (admin only, requires users/write scope)',
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'email_verified',
            type: 'boolean',
            description: 'Email verification status (admin only, requires users/write scope; ignored on update for non-admin users)',
            nullable: true,
            example: true
        ),
        new OA\Property(
            property: 'active',
            type: 'boolean',
            description: 'Account active status (admin only, requires users/write scope; ignored on update for non-admin users)',
            nullable: true,
            example: true
        ),
        new OA\Property(
            property: 'identifier',
            type: 'string',
            description: 'User identifier',
            nullable: true,
            example: 'user-12345'
        ),
    ]
)]
class UserFieldsSchema
{
}
