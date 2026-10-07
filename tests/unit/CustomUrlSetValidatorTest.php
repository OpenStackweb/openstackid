<?php namespace Tests\unit;

/**
 * Copyright 2026 OpenStack Foundation
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

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use OAuth2\Models\IClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Validators\CustomValidator;

/**
 * Class CustomUrlSetValidatorTest
 *
 * Regression: custom_url_set rejected https URLs whose hostname contains a hyphen
 * AND carries an explicit port (e.g. https://macbook-air.example.ts.net:10000/auth/callback),
 * because the host part of the sslurl regex did not allow '-' and the path part does not allow ':'.
 *
 * @package Tests\unit
 */
class CustomUrlSetValidatorTest extends TestCase
{
    private function validate(string $value, string $app_type = IClient::ApplicationType_JS_Client): bool
    {
        $data = ['redirect_uris' => $value, 'application_type' => $app_type];
        $validator = new CustomValidator
        (
            new Translator(new ArrayLoader(), 'en'),
            $data,
            ['redirect_uris' => 'custom_url_set:application_type']
        );
        return $validator->passes();
    }

    public static function validUrlsProvider(): array
    {
        return [
            'hyphenated host with port'      => ['https://macbook-air.tail3c93e5.ts.net:10000/auth/callback'],
            'hyphenated host with port root' => ['https://macbook-air.tail3c93e5.ts.net:10000/'],
            'hyphenated host without port'   => ['https://macbook-air.tail3c93e5.ts.net/auth/callback'],
            'double hyphen host'             => ['https://pr-1031--show-admin-preview.netlify.app/auth/callback'],
            'ip with port'                   => ['https://192.168.68.106:8080/auth/callback'],
            'localhost with port'            => ['https://localhost:3000/auth/callback'],
            'plain host'                     => ['https://showadmin.dev.fnopen.com/auth/callback'],
        ];
    }

    #[DataProvider('validUrlsProvider')]
    public function testAcceptsValidSslUrl(string $url): void
    {
        $this->assertTrue($this->validate($url), sprintf('%s should be accepted', $url));
    }

    public function testAcceptsCommaSeparatedSetWithHyphenatedHostAndPort(): void
    {
        $this->assertTrue($this->validate(implode(',', [
            'https://localhost:8080/auth/callback',
            'https://macbook-air.tail3c93e5.ts.net:10000/auth/callback',
        ])));
    }

    public static function invalidUrlsProvider(): array
    {
        return [
            'http scheme'                  => ['http://my-host.com:8080/cb'],
            'no scheme'                    => ['my-host.com:8080/cb'],
            'host starting with hyphen'    => ['https://-my-host.com:8080/cb'],
        ];
    }

    #[DataProvider('invalidUrlsProvider')]
    public function testRejectsInvalidSslUrl(string $url): void
    {
        $this->assertFalse($this->validate($url), sprintf('%s should be rejected', $url));
    }

    public function testRejectsSetWhenAnyUrlIsInvalid(): void
    {
        $this->assertFalse($this->validate(implode(',', [
            'https://macbook-air.tail3c93e5.ts.net:10000/auth/callback',
            'http://localhost:8080/auth/callback',
        ])));
    }
}
