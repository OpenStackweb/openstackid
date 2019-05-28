<?php
/**
 * Copyright 2015 OpenStack Foundation
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
use Illuminate\Support\Facades\DB;
use Models\ServerConfiguration;
use Illuminate\Database\Seeder;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class ServerConfigurationSeeder
 */
class ServerConfigurationSeeder extends Seeder {

    public function run()
    {
        DB::table('server_configuration')->delete();

        $configs = [
            [
                'key'   => 'BlacklistSecurityPolicy.MinutesWithoutExceptions',
                'value' => '5',
            ],
            [
                'key'   => 'BannedIpLifeTimeSeconds',
                'value' => '21600',
            ],
            [
                'key'   => 'Assets.Url',
                'value' => 'http://www.openstack.org/',
            ],
            [
                'key'   => 'Nonce.Lifetime',
                'value' => '360',
            ],
            [
                'key'   => 'MaxFailed.LoginAttempts.2ShowCaptcha',
                'value' => '3',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.InvalidNonceInitialDelay',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.MaxInvalidNonceAttempts',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.ReplayAttackExceptionInitialDelay',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.MaxOpenIdInvalidRealmExceptionAttempts',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.InvalidOpenIdMessageExceptionInitialDelay',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.MaxInvalidOpenIdMessageExceptionAttempts',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.InvalidOpenIdMessageModeInitialDelay',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.MaxInvalidOpenIdMessageModeAttempts',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.OpenIdInvalidRealmExceptionInitialDelay',
                'value' => '10',
            ],
            [
                'key'   => 'Private.Association.Lifetime',
                'value' => '240',
            ],
            [
                'key'   => 'Session.Association.Lifetime',
                'value' => '21600',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.OAuth2.BearerTokenDisclosureAttemptInitialDelay',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.OAuth2.MaxInvalidBearerTokenDisclosureAttempt',
                'value' => '3',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.OAuth2.InvalidAuthorizationCodeInitialDelay',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.OAuth2.MaxInvalidAuthorizationCodeAttempts',
                'value' => '3',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.OAuth2.AuthCodeReplayAttackInitialDelay',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.OAuth2.MaxAuthCodeReplayAttackAttempts',
                'value' => '3',
            ],
            [
                'key'   => 'MaxFailed.Login.Attempts',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.AuthenticationExceptionInitialDelay',
                'value' => '20',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.MaxAuthenticationExceptionAttempts',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.InvalidOpenIdAuthenticationRequestModeInitialDelay',
                'value' => '10',
            ],
            [
                'key'   => 'BlacklistSecurityPolicy.MaxInvalidOpenIdAuthenticationRequestModeAttempts',
                'value' => '10',
            ],
        ];

        foreach ($configs as $config){
            self::createServerConfiguration($config);
        }
    }

    public static function createServerConfiguration(array $payload){
        $config = new ServerConfiguration();
        $config->setKey(trim($payload['key']));
        $config->setValue(trim($payload['value']));
        EntityManager::persist($config);

        EntityManager::flush();
    }

} 