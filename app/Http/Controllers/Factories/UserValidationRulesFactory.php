<?php namespace App\Http\Controllers;
/**
 * Copyright 2020 OpenStack Foundation
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
use Auth\User;
/**
 * Class UserValidationRulesFactory
 * @package App\Http\Controllers
 */
final class UserValidationRulesFactory
{
    /**
     * @param array $data
     * @param false $update
     * @param User|null $currentUser
     * @return string[]
     */
    public static function build(array $data, $update = false, ?User $currentUser = null){

        if($update){
            $rules =  [
                'first_name' => 'sometimes|string',
                'last_name' => 'sometimes|string',
                'email' => 'sometimes|email',
                'identifier' => 'sometimes|string',
                'bio' => 'nullable|string',
                'address1' => 'nullable|string',
                'address2' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'post_code' => 'nullable|string',
                'country_iso_code' => 'nullable|country_iso_alpha2_code',
                'second_email' => 'nullable|email',
                'third_email' => 'nullable|email',
                'gender' => 'nullable|string',
                'gender_specify' => 'nullable|string',
                'statement_of_interest' => 'nullable|string',
                'irc' => 'nullable|string',
                'linked_in_profile' => 'nullable|string',
                'github_user' => 'nullable|string',
                'wechat_user' => 'nullable|string',
                'twitter_name' => 'nullable|string',
                'language' => 'nullable|string',
                'birthday' => 'nullable|date_format:U',
                'password' => 'sometimes|string|confirmed|password_policy',
                'phone_number' => 'nullable|string',
                'company' => 'nullable|string',
                'job_title' => 'nullable|string',
                // admin fields
                'email_verified' => 'nullable|boolean',
                'active' => 'nullable|boolean',
                'groups' => 'sometimes|int_array',
                'public_profile_show_photo' => 'sometimes|boolean',
                'public_profile_show_fullname' => 'sometimes|boolean',
                'public_profile_show_email' => 'sometimes|boolean',
                'public_profile_show_social_media_info' => 'sometimes|boolean',
                'public_profile_show_bio' => 'sometimes|boolean',
                'public_profile_allow_chat_with_me' => 'sometimes|boolean',
                'public_profile_show_telephone_number' => 'sometimes|boolean',
            ];

            if(!is_null($currentUser) && !$currentUser->isAdmin() && $currentUser->hasPasswordSet()){
                $rules['current_password'] = 'required_with:password';
            }

            return $rules;
        }

        return [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'email' => 'required|email',
            'identifier' => 'sometimes|string',
            'bio' => 'nullable|string',
            'address1' => 'nullable|string',
            'address2' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'post_code' => 'nullable|string',
            'country_iso_code' => 'nullable|country_iso_alpha2_code',
            'second_email' => 'nullable|email',
            'third_email' => 'nullable|email',
            'gender' => 'nullable|string',
            'statement_of_interest' => 'nullable|string',
            'irc' => 'nullable|string',
            'linked_in_profile' => 'nullable|string',
            'github_user' => 'nullable|string',
            'wechat_user' => 'nullable|string',
            'twitter_name' => 'nullable|string',
            'language' => 'nullable|string',
            'birthday' => 'nullable|date_format:U',
            'password' => 'sometimes|string|confirmed|password_policy',
            'phone_number' => 'nullable|string',
            'company' => 'nullable|string',
            'job_title' => 'nullable|string',
            // admin fields
            'email_verified' => 'nullable|boolean',
            'active' => 'nullable|boolean',
            'groups' => 'sometimes|int_array',
            'public_profile_show_photo' => 'sometimes|boolean',
            'public_profile_show_fullname' => 'sometimes|boolean',
            'public_profile_show_email' => 'sometimes|boolean',
            'public_profile_show_social_media_info' => 'sometimes|boolean',
            'public_profile_show_bio' => 'sometimes|boolean',
            'public_profile_allow_chat_with_me' => 'sometimes|boolean',
            'public_profile_show_telephone_number' => 'sometimes|boolean',
        ];
    }
}