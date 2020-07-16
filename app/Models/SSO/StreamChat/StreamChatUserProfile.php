<?php namespace App\Models\SSO\StreamChat;
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

/**
 * Class StreamChatUserProfile
 * @package App\Models\SSO\StreamChat
 */
final class StreamChatUserProfile
{
    /**
     * @var string
     */
    private $user_id;

    /**
     * @var string
     */
    private $user_name;

    /**
     * @var string
     */
    private $user_image;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $api_key;

    /**
     * StreamChatUserProfile constructor.
     * @param string $user_id
     * @param string $user_name
     * @param string $user_image
     * @param string $token
     * @param string $api_key
     */
    public function __construct(string $user_id, string $user_name, string $user_image, string $token, string $api_key)
    {
        $this->user_id = $user_id;
        $this->user_name = $user_name;
        $this->user_image = $user_image;
        $this->token = $token;
        $this->api_key = $api_key;
    }

    /**
     * @return array
     */
    public function serialize(){
        $data = [
            "id" => $this->user_id,
            "name" => $this->user_name,
            "image" => $this->user_image,
            "token" => $this->token,
            "api_key" => $this->api_key
        ];
        return $data;
    }

}