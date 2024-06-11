<?php namespace Services\OAuth2;
/**
 * Copyright 2016 OpenStack Foundation
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

use Models\OAuth2\Client;
use OAuth2\Models\IClient;
use OAuth2\OAuth2Protocol;
use OAuth2\Services\IClientCredentialGenerator;
use Laminas\Math\Rand;
use DateTime;
use DateInterval;
/**
 * Class ClientCredentialGenerator
 * @package Services\OAuth2
 */
final class ClientCredentialGenerator implements IClientCredentialGenerator
{

    /**
     * @param Client $client
     * @param bool $only_secret
     * @return Client
     * @throws \Exception
     */
    public function generate(Client $client, $only_secret = false)
    {
        if(!$only_secret)
            $client->setClientId(Rand::getString(32, OAuth2Protocol::VsChar, true) . IClientCredentialGenerator::ClientSuffix);

        if ($client->getClientType() === IClient::ClientType_Confidential)
        {
            $now = new DateTime('now', new \DateTimeZone('UTC'));
            $client->setClientSecret(Rand::getString(64, OAuth2Protocol::VsChar, true));
            // default 6 months
            $client->setClientSecretExpiresAt($now->add( new DateInterval('P6M')));
        }
        return $client;
    }
}