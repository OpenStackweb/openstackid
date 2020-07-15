<?php namespace App\libs\Auth\Factories;
/**
 * Copyright 2019 OpenStack Foundation
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
use Auth\Group;
use Auth\User;
use Illuminate\Support\Facades\Auth;
/**
 * Class UserFactory
 * @package App\libs\Auth\Factories
 */
final class UserFactory
{
    /**
     * @param array $payload
     * @return User
     */
    public static function build(array $payload):User{
        $user =  self::populate(new User, $payload);
        // set the created by user
        $current_user = Auth::user();
        if(!is_null($current_user) && $current_user->isSuperAdmin()){
            $user->setCreatedBy($current_user);
        }
        return $user;
    }

    /**
     * @param User $user
     * @param array $payload
     * @return User
     */
    public static function populate(User $user, array $payload):User{

        if(isset($payload['first_name']))
            $user->setFirstName(trim($payload['first_name']));
        if(isset($payload['last_name']))
            $user->setLastName(trim($payload['last_name']));

        if(isset($payload['email']))
            $user->setEmail(strtolower(trim($payload['email'])));

        if(isset($payload['second_email']))
            $user->setSecondEmail(strtolower(trim($payload['second_email'])));
        if(isset($payload['third_email']))
            $user->setThirdEmail(strtolower(trim($payload['third_email'])));

        if(isset($payload['bio']))
            $user->setBio(trim($payload['bio']));

        if(isset($payload['identifier']) && !empty($payload['identifier']))
            $user->setIdentifier(trim($payload['identifier']));

        if(isset($payload['statement_of_interest']))
            $user->setStatementOfInterest(trim($payload['statement_of_interest']));

        if(isset($payload['irc']))
            $user->setIrc(trim($payload['irc']));

        if(isset($payload['github_user']))
            $user->setGithubUser(trim($payload['github_user']));

        if(isset($payload['twitter_name']))
            $user->setTwitterName(trim($payload['twitter_name']));

        if(isset($payload['wechat_user']))
            $user->setWechatUser(trim($payload['wechat_user']));

        if(isset($payload['linked_in_profile']))
            $user->setLinkedInProfile(trim($payload['linked_in_profile']));

        if(isset($payload['birthday'])){
            if(!empty($payload['birthday'])) {
                $birthday = $payload['birthday'];
                if (is_int($birthday)) {
                    $birthday = new \DateTime("@$birthday");
                }
                $birthday->setTime(0, 0, 0);
                $user->setBirthday($birthday);
            }
            else{
                $user->setBirthday(null);
            }
        }

        if(isset($payload['password_enc']) && !empty($payload['password_enc'])) {
            $user->setPasswordEnc(trim($payload['password_enc']));
        }

        if(isset($payload['password']) && !empty($payload['password'])) {
            $user->setPassword(trim($payload['password']));
        }

        if(isset($payload['gender']))
            $user->setGender(trim($payload['gender']));

        if(isset($payload['gender_specify']))
            $user->setGenderSpecify(trim($payload['gender_specify']));

        if(isset($payload['address1']))
            $user->setAddress1(trim($payload['address1']));

        if(isset($payload['address2']))
            $user->setAddress2(trim($payload['address2']));

        if(isset($payload['city']))
            $user->setCity(trim($payload['city']));

        if(isset($payload['state']))
            $user->setState(trim($payload['state']));

        if(isset($payload['phone_number']))
            $user->setPhoneNumber(trim($payload['phone_number']));

        if(isset($payload['company']))
            $user->setCompany(trim($payload['company']));

        if(isset($payload['post_code']))
            $user->setPostCode(trim($payload['post_code']));

        if(isset($payload['country_iso_code']))
            $user->setCountryIsoCode(trim($payload['country_iso_code']));

        if(isset($payload['language']))
            $user->setLanguage(trim($payload['language']));

        if(isset($payload['groups'])){
            foreach($payload['groups'] as $group){
                if(!$group instanceof Group) continue;
                $user->addToGroup($group);
            }
        }

        if(isset($payload['active'])) {
            $active = boolval($payload['active']);
            if($active)
                $user->activate();
            else
                $user->deActivate();
        }

        if(isset($payload['public_profile_show_photo']))
            $user->setPublicProfileShowPhoto(boolval($payload['public_profile_show_photo']));

        if(isset($payload['public_profile_show_fullname']))
            $user->setPublicProfileShowFullname(boolval($payload['public_profile_show_fullname']));

        if(isset($payload['public_profile_show_email']))
            $user->setPublicProfileShowEmail(boolval($payload['public_profile_show_email']));

        if(isset($payload['email_verified']) && $payload['email_verified'] == true && !$user->isEmailVerified())
            $user->verifyEmail();

        return $user;
    }
}