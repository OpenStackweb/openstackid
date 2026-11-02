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

use DateTime;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use jwk\IJWK;
use Mockery;
use Models\OAuth2\ServerPrivateKey;
use OAuth2\Repositories\IServerPrivateKeyRepository;
use phpseclib3\Crypt\PublicKeyLoader;
use PHPUnit\Framework\TestCase;
use Services\OAuth2\ServerPrivateKeyService;
use Utils\Db\ITransactionService;
use Validators\CustomValidator;

/**
 * Class ServerPrivateKeyTest
 *
 * Server key creation and public key derivation: both feed /oauth2/certs and
 * the admin key screens. Results are checked with ext-openssl, independently
 * of the library the code uses.
 *
 * @package Tests\unit
 */
class ServerPrivateKeyTest extends TestCase
{
    private const Password = 'server-key-password';

    private static string $private_pem = '';
    private static string $expected_public_pem = '';

    public static function setUpBeforeClass(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, self::$private_pem);
        self::$expected_public_pem = openssl_pkey_get_details($key)['key'];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function service(): ServerPrivateKeyService
    {
        $repository = Mockery::mock(IServerPrivateKeyRepository::class);
        $repository->shouldReceive('getByValidityRange')->andReturn([]);
        $repository->shouldReceive('add');

        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->andReturnUsing(fn(callable $callback) => $callback());

        return new ServerPrivateKeyService($repository, $tx_service);
    }

    private function params(array $extra = []): array
    {
        return array_merge([
            'kid'        => 'test-kid',
            'valid_from' => '2026-01-01 00:00:00',
            'valid_to'   => '2036-01-01 00:00:00',
            'type'       => 'RSA',
            'usage'      => 'sig',
            'alg'        => 'RS256',
            'active'     => true,
        ], $extra);
    }

    private function buildKey(string $pem, ?string $password = null): ServerPrivateKey
    {
        return ServerPrivateKey::build
        (
            'test-kid',
            new DateTime('2026-01-01'),
            new DateTime('2036-01-01'),
            'RSA',
            'sig',
            'RS256',
            true,
            $pem,
            $password
        );
    }

    private function assertSamePem(string $expected, string $actual): void
    {
        $this->assertSame(preg_replace('/\s+/', '', $expected), preg_replace('/\s+/', '', $actual));
    }

    public function testGetPublicKeyPEMFromPlainKey(): void
    {
        $this->assertSamePem(self::$expected_public_pem, $this->buildKey(self::$private_pem)->getPublicKeyPEM());
    }

    public function testGetPublicKeyPEMFromEncryptedKey(): void
    {
        $encrypted = PublicKeyLoader::load(self::$private_pem)->withPassword(self::Password)->toString('PKCS1');

        $this->assertSamePem(self::$expected_public_pem, $this->buildKey($encrypted, self::Password)->getPublicKeyPEM());
    }

    public function testCreateWithoutPasswordGeneratesPlain2048BitsPKCS1Key(): void
    {
        $pem = (string) $this->service()->create($this->params())->getPEM();

        $this->assertStringContainsString('-----BEGIN RSA PRIVATE KEY-----', $pem);
        $this->assertStringNotContainsString('ENCRYPTED', $pem);

        $key = openssl_pkey_get_private($pem);
        $this->assertNotFalse($key);
        $this->assertSame(2048, openssl_pkey_get_details($key)['bits']);
    }

    public function testCreateWithPasswordEncryptsTheKey(): void
    {
        $pem = (string) $this->service()->create($this->params(['password' => self::Password]))->getPEM();

        $this->assertStringContainsString('-----BEGIN RSA PRIVATE KEY-----', $pem);
        $this->assertStringContainsString('ENCRYPTED', $pem);
        $this->assertFalse(@openssl_pkey_get_private($pem), 'must not load without the password');
        $this->assertFalse(@openssl_pkey_get_private($pem, 'wrong-password'), 'must not load with a wrong password');
        $this->assertNotFalse(openssl_pkey_get_private($pem, self::Password));
    }

    public function testPasswordDoesNotLeakIntoTheNextCreate(): void
    {
        $service = $this->service();

        $service->create($this->params(['password' => self::Password]));
        $pem = (string) $service->create($this->params())->getPEM();

        $this->assertStringNotContainsString('ENCRYPTED', $pem);
        $this->assertNotFalse(openssl_pkey_get_private($pem));
    }

    public function testCreateKeepsAProvidedPem(): void
    {
        $key = $this->service()->create($this->params(['pem_content' => self::$private_pem]));

        $this->assertSame(trim(self::$private_pem), $key->getPEM());
    }

    public function testGeneratedKeysPassTheAdminValidatorsAndBuildAJWK(): void
    {
        foreach ([null, self::Password] as $password) {
            $key = $this->service()->create($this->params($password ? ['password' => $password] : []));
            $pem = (string) $key->getPEM();

            $validator = new CustomValidator(new Translator(new ArrayLoader(), 'en'), ['password' => $password], []);

            $this->assertTrue($validator->validatePrivateKeyPem('pem_content', $pem, ['password']));
            $this->assertTrue($validator->validatePrivateKeyPemLength('pem_content', $pem, ['password']));
            $this->assertInstanceOf(IJWK::class, $key->toJWK());
            $this->assertStringContainsString('-----BEGIN PUBLIC KEY-----', $key->getPublicKeyPEM());
        }
    }
}
