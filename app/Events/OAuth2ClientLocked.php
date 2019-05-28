<?php namespace App\Events;
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
use Illuminate\Queue\SerializesModels;
/**
 * Class OAuth2ClientLocked
 * @package App\Events
 */
final class OAuth2ClientLocked
{
    use SerializesModels;

    /**
     * @var string
     */
    private $client_id;

    /**
     * OAuth2ClientLocked constructor.
     * @param string $client_id
     */
    public function __construct(string $client_id)
    {
        $this->client_id = $client_id;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->client_id;
    }
}