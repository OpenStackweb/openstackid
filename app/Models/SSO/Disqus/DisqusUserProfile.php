<?php namespace App\Models\SSO\Disqus;
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
use App\Models\SSO\DisqusSSOProfile;
use Auth\User;

/**
 * Class DisqusUserProfile
 * @package App\Models\SSO\Disqus
 */
class DisqusUserProfile
{
    /**
     * @var string
     */
    private $public_key;

    /**
     * @var string
     */
    private $user_id;

    /**
     * @var string
     */
    private $user_email;

    /**
     * @var string
     */
    private $user_name;

    /**
     * @var string
     */
    private $private_key;

    /**
     * DisqusUserProfile constructor.
     * @param DisqusSSOProfile $profile
     * @param User $user
     */
    public function __construct(DisqusSSOProfile $profile, User $user)
    {
        $this->public_key = $profile->getPublicKey();
        $this->private_key = $profile->getSecretKey();
        $this->user_id = $user->getId();
        $this->user_email = $user->getEmail();
        $this->user_name = $user->getNickName();
    }

    /**
     * @param string $data
     * @param string $key
     * @return string
     */
    private function dsq_hmacsha1(string $data, string $key):string {
        $blocksize=64;
        $hashfunc='sha1';
        if (strlen($key)>$blocksize)
            $key=pack('H*', $hashfunc($key));
        $key=str_pad($key,$blocksize,chr(0x00));
        $ipad=str_repeat(chr(0x36),$blocksize);
        $opad=str_repeat(chr(0x5c),$blocksize);
        $hmac = pack(
            'H*',$hashfunc(
                ($key^$opad).pack(
                    'H*',$hashfunc(
                        ($key^$ipad).$data
                    )
                )
            )
        );
        return bin2hex($hmac);
    }

    /**
     * @return array
     */
    public function serialize(){
        $data = [
            "id" => $this->user_id,
            "username" => $this->user_name,
            "email" => $this->user_email
        ];

        $message = base64_encode(json_encode($data));
        $timestamp = time();
        $hmac = $this->dsq_hmacsha1($message . ' ' . $timestamp, $this->private_key);

        return [
            'auth' =>  $message." ".$hmac." ".$timestamp,
            'public_key' => $this->public_key
        ];
    }

}