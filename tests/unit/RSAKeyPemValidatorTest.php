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
use phpseclib3\Crypt\PublicKeyLoader;
use PHPUnit\Framework\TestCase;
use Validators\CustomValidator;

/**
 * Class RSAKeyPemValidatorTest
 *
 * Contract of the rules guarding client public keys and server private keys
 * (public_key_pem, public_key_pem_length, private_key_pem, private_key_pem_length,
 * private_key_password). Keys come from ext-openssl so the expected results do
 * not depend on the library the validators use.
 *
 * @package Tests\unit
 */
class RSAKeyPemValidatorTest extends TestCase
{
    private const Password = 'right-password';

    private static string $pkcs8_private = '';
    private static string $pkcs1_private = '';
    private static string $pkcs1_private_encrypted = '';
    private static string $pkcs8_public = '';
    private static string $pkcs1_public = '';
    private static string $short_private = '';
    private static string $short_public = '';
    private static string $ec_public = '';

    public static function setUpBeforeClass(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, self::$pkcs8_private);
        self::$pkcs8_public = openssl_pkey_get_details($key)['key'];

        // ext-openssl only exports PKCS8: this is fixture conversion, not an expected value
        $loaded = PublicKeyLoader::load(self::$pkcs8_private);
        self::$pkcs1_private = $loaded->toString('PKCS1');
        self::$pkcs1_private_encrypted = $loaded->withPassword(self::Password)->toString('PKCS1');
        self::$pkcs1_public = $loaded->getPublicKey()->toString('PKCS1');

        $short = openssl_pkey_new(['private_key_bits' => 1024, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($short, self::$short_private);
        self::$short_public = openssl_pkey_get_details($short)['key'];

        $ec = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        self::$ec_public = openssl_pkey_get_details($ec)['key'];
    }

    private function validator(array $data = []): CustomValidator
    {
        return new CustomValidator(new Translator(new ArrayLoader(), 'en'), $data, []);
    }

    public function testPublicKeyPemAcceptsPKCS8AndPKCS1(): void
    {
        $this->assertTrue($this->validator()->validatePublicKeyPem('pem_content', self::$pkcs8_public));
        $this->assertTrue($this->validator()->validatePublicKeyPem('pem_content', self::$pkcs1_public));
    }

    public function testPublicKeyPemRejectsAnythingElse(): void
    {
        $validator = $this->validator();

        $this->assertFalse($validator->validatePublicKeyPem('pem_content', ''), 'empty');
        $this->assertFalse($validator->validatePublicKeyPem('pem_content', 'not a pem'), 'no headers');
        $this->assertFalse(
            $validator->validatePublicKeyPem('pem_content', "-----BEGIN PUBLIC KEY-----\nzzzz\n-----END PUBLIC KEY-----"),
            'garbage between the headers'
        );
        $this->assertFalse(
            $validator->validatePublicKeyPem('pem_content', substr(self::$pkcs8_public, 0, 120) . "\n-----END PUBLIC KEY-----"),
            'truncated body'
        );
        $this->assertFalse($validator->validatePublicKeyPem('pem_content', self::$pkcs8_private), 'private key');
        $this->assertFalse($validator->validatePublicKeyPem('pem_content', self::$ec_public), 'EC key');
    }

    public function testPublicKeyPemLengthRequiresMoreThan1024Bits(): void
    {
        $validator = $this->validator();

        $this->assertTrue($validator->validatePublicKeyPemLength('pem_content', self::$pkcs8_public));
        $this->assertFalse($validator->validatePublicKeyPemLength('pem_content', self::$short_public));
        $this->assertFalse($validator->validatePublicKeyPemLength('pem_content', 'not a pem'));
        $this->assertFalse($validator->validatePublicKeyPemLength('pem_content', ''));
    }

    public function testPrivateKeyPemAcceptsPlainKeys(): void
    {
        $validator = $this->validator();

        $this->assertTrue($validator->validatePrivateKeyPem('pem_content', self::$pkcs8_private, ['password']));
        $this->assertTrue($validator->validatePrivateKeyPem('pem_content', self::$pkcs1_private, ['password']));
    }

    public function testPrivateKeyPemOnEncryptedKeyNeedsTheRightPassword(): void
    {
        $pem = self::$pkcs1_private_encrypted;

        $this->assertTrue($this->validator(['password' => self::Password])->validatePrivateKeyPem('pem_content', $pem, ['password']));
        $this->assertFalse($this->validator(['password' => 'wrong'])->validatePrivateKeyPem('pem_content', $pem, ['password']));
        $this->assertFalse($this->validator()->validatePrivateKeyPem('pem_content', $pem, ['password']));
    }

    public function testPrivateKeyPemRejectsAnythingElse(): void
    {
        $validator = $this->validator();

        $this->assertFalse($validator->validatePrivateKeyPem('pem_content', '', ['password']), 'empty');
        $this->assertFalse($validator->validatePrivateKeyPem('pem_content', 'not a pem', ['password']), 'no headers');
        $this->assertFalse(
            $validator->validatePrivateKeyPem('pem_content', "-----BEGIN PRIVATE KEY-----\nzzzz\n-----END PRIVATE KEY-----", ['password']),
            'garbage between the headers'
        );
        $this->assertFalse($validator->validatePrivateKeyPem('pem_content', self::$pkcs8_public, ['password']), 'public key');
    }

    public function testPrivateKeyPemLengthRequiresAtLeast2048Bits(): void
    {
        $this->assertTrue($this->validator()->validatePrivateKeyPemLength('pem_content', self::$pkcs8_private, ['password']));
        $this->assertFalse($this->validator()->validatePrivateKeyPemLength('pem_content', self::$short_private, ['password']));
        $this->assertTrue(
            $this->validator(['password' => self::Password])
                ->validatePrivateKeyPemLength('pem_content', self::$pkcs1_private_encrypted, ['password'])
        );
        $this->assertFalse(
            $this->validator(['password' => 'wrong'])
                ->validatePrivateKeyPemLength('pem_content', self::$pkcs1_private_encrypted, ['password'])
        );
    }

    public function testPrivateKeyPasswordChecksItAgainstTheKey(): void
    {
        $encrypted = ['pem_content' => self::$pkcs1_private_encrypted];

        $this->assertTrue($this->validator($encrypted)->validatePrivateKeyPassword('password', self::Password, ['pem_content']));
        $this->assertFalse($this->validator($encrypted)->validatePrivateKeyPassword('password', 'wrong', ['pem_content']));
        $this->assertTrue(
            $this->validator(['pem_content' => self::$pkcs1_private])->validatePrivateKeyPassword('password', 'any', ['pem_content']),
            'a plain key loads with any password'
        );
        $this->assertTrue($this->validator()->validatePrivateKeyPassword('password', 'any', ['pem_content']), 'nothing to check against');
    }
}
