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
use Closure;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use jwa\cryptographic_algorithms\DigitalSignatures_MACs_Registry;
use jwa\cryptographic_algorithms\KeyManagementAlgorithms_Registry;
use jwa\JSONWebSignatureAndEncryptionAlgorithms;
use jwe\impl\JWEFactory;
use jwe\impl\specs\JWE_CompactFormatSpecification;
use jwe\impl\specs\JWE_ParamsSpecification;
use jwk\IJWK;
use jwk\impl\JWKSet;
use jwk\impl\RSAJWKFactory;
use jwk\impl\RSAJWKPEMPrivateKeySpecification;
use jwk\JSONWebKeyPublicKeyUseValues;
use jws\impl\specs\JWS_ParamsSpecification;
use jws\JWSFactory;
use jwt\RegisteredJWTClaimNames;
use jwt\utils\JWTClaimSetFactory;
use Mockery;
use OAuth2\Exceptions\RecipientKeyNotFoundException;
use OAuth2\Heuristics\ClientEncryptionKeyFinder;
use OAuth2\Heuristics\ClientSigningKeyFinder;
use OAuth2\Models\ClientAssertionAuthenticationContext;
use OAuth2\Models\IClient;
use OAuth2\Models\IClientPublicKey;
use OAuth2\Models\TokenEndpointAuthInfo;
use OAuth2\OAuth2Protocol;
use OAuth2\Strategies\ClientPrivateKeyAssertionAuthContextValidator;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Services\OAuth2\HttpIClientJWKSetReader;
use Utils\Db\ITransactionService;
use utils\json_types\StringOrURI;

/**
 * Clients can publish their keys at a jwks_uri instead of storing them. Runs the reader and
 * the code that consumes it against a real local HTTP server, so headers, redirects and
 * timeouts are the ones Guzzle actually sees.
 *
 * Class ClientJWKSetReaderTest
 * @package Tests\unit
 */
class ClientJWKSetReaderTest extends TestCase
{
    private const TokenEndpoint = 'https://idp.example.com/oauth2/token';
    private const ClientId      = 'jwks-client';
    private const SigKid        = 'sig-1';
    private const EncKid        = 'enc-1';

    /** @var resource */
    private static $server;
    private static string $base_url = '';
    private static string $jwks_file = '';
    private static string $sig_private_pem = '';
    private static string $enc_private_pem = '';
    private static string $other_private_pem = '';

    public static function setUpBeforeClass(): void
    {
        $sig = RSA::createKey(2048);
        $enc = RSA::createKey(2048);
        self::$sig_private_pem   = $sig->toString('PKCS8');
        self::$enc_private_pem   = $enc->toString('PKCS8');
        self::$other_private_pem = RSA::createKey(2048)->toString('PKCS8');

        self::$jwks_file = tempnam(sys_get_temp_dir(), 'jwks');
        file_put_contents(self::$jwks_file, json_encode(['keys' => [
            self::publicJWK($sig, self::SigKid, JSONWebKeyPublicKeyUseValues::Signature, JSONWebSignatureAndEncryptionAlgorithms::RS256),
            self::publicJWK($enc, self::EncKid, JSONWebKeyPublicKeyUseValues::Encryption, JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP),
        ]]));

        $port = self::freePort();
        self::$base_url = sprintf('http://127.0.0.1:%d', $port);
        self::$server = proc_open(
            [PHP_BINARY, '-S', sprintf('127.0.0.1:%d', $port), __DIR__ . '/../fixtures/jwks_server.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            null,
            // the slow route must not hold back the tests that run after it
            ['JWKS_FILE' => self::$jwks_file, 'PHP_CLI_SERVER_WORKERS' => '3'] + getenv()
        );

        $deadline = microtime(true) + 10;
        while (microtime(true) < $deadline) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($socket !== false) {
                fclose($socket);
                return;
            }
            usleep(50000);
        }
        self::fail('local JWKS server did not start');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        @unlink(self::$jwks_file);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Facade::clearResolvedInstances();
        $this->useConfig(['timeout' => 10, 'allow_redirects' => false, 'verify_ssl_cert' => true]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        parent::tearDown();
    }

    /**
     * @dataProvider jsonRoutes
     */
    public function testReadsKeySetServedWithAJsonMediaType(string $route): void
    {
        $set = (new HttpIClientJWKSetReader())->read($this->clientAt($route));

        $this->assertInstanceOf(JWKSet::class, $set);
        $this->assertCount(2, $set->getKeys());
        $this->assertNotNull($set->getKeyById(self::SigKid));
        $this->assertNotNull($set->getKeyById(self::EncKid));
    }

    public static function jsonRoutes(): array
    {
        return [
            'application/json'                 => ['/json'],
            'with a charset'                   => ['/json-charset'],
            'the value the IDP serves itself'  => ['/idp-style'],
            'RFC 7517 JWK set media type'      => ['/jwk-set'],
            'media types are case insensitive' => ['/uppercase'],
        ];
    }

    /**
     * @dataProvider unusableRoutes
     */
    public function testReturnsNullWhenTheResponseIsNotAKeySet(string $route): void
    {
        $this->assertNull((new HttpIClientJWKSetReader())->read($this->clientAt($route)));
    }

    public static function unusableRoutes(): array
    {
        return [
            'html'            => ['/html'],
            'no content type' => ['/no-content-type'],
            '404'             => ['/not-found'],
            '500'             => ['/error'],
        ];
    }

    public function testReturnsNullWhenTheClientHasNoJwksUri(): void
    {
        $client = Mockery::mock(IClient::class);
        $client->shouldReceive('getJWKSUri')->andReturnNull();

        $this->assertNull((new HttpIClientJWKSetReader())->read($client));
    }

    public function testReturnsNullWhenTheHostCannotBeReached(): void
    {
        $client = Mockery::mock(IClient::class);
        $client->shouldReceive('getJWKSUri')->andReturn(sprintf('http://127.0.0.1:%d/json', self::freePort()));

        $this->assertNull((new HttpIClientJWKSetReader())->read($client));
    }

    public function testDoesNotFollowRedirectsByDefault(): void
    {
        $this->assertNull((new HttpIClientJWKSetReader())->read($this->clientAt('/redirect')));
    }

    public function testFollowsRedirectsWhenConfiguredTo(): void
    {
        $this->useConfig(['timeout' => 10, 'allow_redirects' => true, 'verify_ssl_cert' => true]);

        $this->assertInstanceOf(JWKSet::class, (new HttpIClientJWKSetReader())->read($this->clientAt('/redirect')));
    }

    public function testGivesUpAfterTheConfiguredTimeout(): void
    {
        $this->useConfig(['timeout' => 1, 'allow_redirects' => false, 'verify_ssl_cert' => true]);

        $started = microtime(true);
        $set     = (new HttpIClientJWKSetReader())->read($this->clientAt('/slow'));

        $this->assertNull($set);
        $this->assertLessThan(2.5, microtime(true) - $started, 'the 3 second response should have been abandoned');
    }

    public function testSigningKeyFinderFindsTheKeyAtTheJwksUri(): void
    {
        $finder = new ClientSigningKeyFinder(new HttpIClientJWKSetReader());

        $key = $finder->find($this->clientAt('/json'), DigitalSignatures_MACs_Registry::getInstance()->get('RS256'));

        $this->assertSame(self::SigKid, $key->getId()->getValue());
    }

    public function testSigningKeyFinderLooksAtTheJwksUriWhenTheKidHintIsNotStored(): void
    {
        $finder = new ClientSigningKeyFinder(new HttpIClientJWKSetReader());

        $key = $finder->find($this->clientAt('/json'), DigitalSignatures_MACs_Registry::getInstance()->get('RS256'), self::SigKid);

        $this->assertSame(self::SigKid, $key->getId()->getValue());
    }

    public function testSigningKeyFinderSkipsAnInactiveStoredKey(): void
    {
        $finder = new ClientSigningKeyFinder(new HttpIClientJWKSetReader());

        $key = $finder->find($this->clientAt('/json', $this->inactiveStoredKey()), DigitalSignatures_MACs_Registry::getInstance()->get('RS256'), 'stored');

        $this->assertSame(self::SigKid, $key->getId()->getValue());
    }

    public function testEncryptionKeyFinderSkipsAnInactiveStoredKey(): void
    {
        $finder = new ClientEncryptionKeyFinder(new HttpIClientJWKSetReader(), $this->transactions());

        $key = $finder->find(
            $this->clientAt('/json', $this->inactiveStoredKey()),
            KeyManagementAlgorithms_Registry::getInstance()->get(JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP),
            'stored'
        );

        $this->assertSame(self::EncKid, $key->getId()->getValue());
    }

    public function testSigningKeyFinderReportsAMissingKeyWhenTheJwksUriFails(): void
    {
        $finder = new ClientSigningKeyFinder(new HttpIClientJWKSetReader());

        $this->expectException(RecipientKeyNotFoundException::class);
        $finder->find($this->clientAt('/not-found'), DigitalSignatures_MACs_Registry::getInstance()->get('RS256'));
    }

    public function testIdTokenEncryptedWithTheKeyAtTheJwksUriCanBeDecryptedByTheClient(): void
    {
        $finder = new ClientEncryptionKeyFinder(new HttpIClientJWKSetReader(), $this->transactions());

        $recipient = $finder->find(
            $this->clientAt('/json'),
            KeyManagementAlgorithms_Registry::getInstance()->get(JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP)
        );

        $this->assertSame(self::EncKid, $recipient->getId()->getValue());

        $compact = JWEFactory::build(new JWE_ParamsSpecification(
            $recipient,
            new StringOrURI(JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP),
            new StringOrURI(JSONWebSignatureAndEncryptionAlgorithms::A128CBC_HS256),
            'id-token'
        ))->toCompactSerialization();

        $client_key = RSAJWKFactory::build(new RSAJWKPEMPrivateKeySpecification(
            self::$enc_private_pem,
            RSAJWKPEMPrivateKeySpecification::WithoutPassword,
            JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP
        ));
        $client_key->setKeyUse(JSONWebKeyPublicKeyUseValues::Encryption)->setId(self::EncKid);

        $jwe = JWEFactory::build(new JWE_CompactFormatSpecification($compact));
        $jwe->setRecipientKey($client_key);

        $this->assertSame('id-token', $jwe->getPlainText());
    }

    public function testEncryptionKeyFinderLooksAtTheJwksUriWhenTheKidHintIsNotStored(): void
    {
        $finder = new ClientEncryptionKeyFinder(new HttpIClientJWKSetReader(), $this->transactions());

        $key = $finder->find(
            $this->clientAt('/json'),
            KeyManagementAlgorithms_Registry::getInstance()->get(JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP),
            self::EncKid
        );

        $this->assertSame(self::EncKid, $key->getId()->getValue());
    }

    public function testEncryptionKeyFinderReportsAMissingKeyWhenTheJwksUriFails(): void
    {
        $finder = new ClientEncryptionKeyFinder(new HttpIClientJWKSetReader(), $this->transactions());

        $this->expectException(RecipientKeyNotFoundException::class);
        $finder->find(
            $this->clientAt('/not-found'),
            KeyManagementAlgorithms_Registry::getInstance()->get(JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP)
        );
    }

    public function testPrivateKeyJwtIsVerifiedWithTheKeyNamedByItsKid(): void
    {
        $this->assertTrue($this->validateAssertion($this->signedAssertion(self::$sig_private_pem, self::SigKid), '/json'));
    }

    public function testPrivateKeyJwtWithoutKidIsVerifiedWithTheSigningKey(): void
    {
        $this->assertTrue($this->validateAssertion($this->signedAssertion(self::$sig_private_pem, null), '/json'));
    }

    public function testPrivateKeyJwtSignedWithAnotherKeyIsRejected(): void
    {
        $this->assertFalse($this->validateAssertion($this->signedAssertion(self::$other_private_pem, self::SigKid), '/json'));
    }

    public function testPrivateKeyJwtIsRejectedWhenTheJwksUriFails(): void
    {
        $this->assertFalse($this->validateAssertion($this->signedAssertion(self::$sig_private_pem, self::SigKid), '/not-found'));
    }

    private function validateAssertion(string $assertion, string $route): bool
    {
        $client = $this->clientAt($route);
        $client->shouldReceive('getTokenEndpointAuthInfo')->andReturn(new TokenEndpointAuthInfo(
            OAuth2Protocol::TokenEndpoint_AuthMethod_PrivateKeyJwt,
            DigitalSignatures_MACs_Registry::getInstance()->get('RS256')
        ));

        $context = new ClientAssertionAuthenticationContext(ClientAssertionAuthenticationContext::RegisteredAssertionType, $assertion);
        $context->setClient($client);

        $validator = new ClientPrivateKeyAssertionAuthContextValidator();
        $validator->setTokenEndpointUrl(self::TokenEndpoint);
        $validator->setJWKSetReader(new HttpIClientJWKSetReader());

        return $validator->validate($context);
    }

    private function signedAssertion(string $private_pem, ?string $kid): string
    {
        $key = RSAJWKFactory::build(new RSAJWKPEMPrivateKeySpecification(
            $private_pem,
            RSAJWKPEMPrivateKeySpecification::WithoutPassword,
            JSONWebSignatureAndEncryptionAlgorithms::RS256
        ));
        if (!is_null($kid)) {
            $key->setId($kid);
        }

        return JWSFactory::build(new JWS_ParamsSpecification(
            $key,
            new StringOrURI(JSONWebSignatureAndEncryptionAlgorithms::RS256),
            JWTClaimSetFactory::build([
                RegisteredJWTClaimNames::Issuer         => self::ClientId,
                RegisteredJWTClaimNames::Subject        => self::ClientId,
                RegisteredJWTClaimNames::Audience       => self::TokenEndpoint,
                RegisteredJWTClaimNames::ExpirationTime => time() + 60,
                RegisteredJWTClaimNames::IssuedAt       => time(),
                RegisteredJWTClaimNames::JWTID          => bin2hex(random_bytes(8)),
            ])
        ))->toCompactSerialization();
    }

    /**
     * A client with no usable stored keys, so everything has to come from its jwks_uri.
     */
    private function clientAt(string $route, ?IClientPublicKey $stored_key = null): IClient
    {
        $client = Mockery::mock(IClient::class);
        $client->shouldReceive('getJWKSUri')->andReturn(self::$base_url . $route);
        $client->shouldReceive('getPublicKeyByIdentifier')->andReturn($stored_key);
        $client->shouldReceive('getCurrentPublicKeyByUse')->andReturnNull();
        return $client;
    }

    private function inactiveStoredKey(): IClientPublicKey
    {
        $key = Mockery::mock(IClientPublicKey::class);
        $key->shouldReceive('isActive')->andReturn(false);
        return $key;
    }

    private function transactions(): ITransactionService
    {
        $service = Mockery::mock(ITransactionService::class);
        $service->shouldReceive('transaction')->andReturnUsing(fn(Closure $callback) => $callback());
        return $service;
    }

    private function useConfig(array $curl): void
    {
        $app = new Container();
        $app->instance('config', new Repository(['curl' => $curl]));
        $app->instance('log', new NullLogger());
        Facade::setFacadeApplication($app);
    }

    private static function publicJWK(RSA\PrivateKey $key, string $kid, string $use, string $alg): array
    {
        $public = json_decode($key->getPublicKey()->toString('JWK'), true)['keys'][0];
        return ['kty' => 'RSA', 'kid' => $kid, 'use' => $use, 'alg' => $alg, 'n' => $public['n'], 'e' => $public['e']];
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port   = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        return $port;
    }
}
