<?php namespace Tests;
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

use Auth\User;
use Database\Seeders\TestSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use jwe\IJWE;
use jwk\impl\RSAJWKFactory;
use jwk\impl\RSAJWKPEMPrivateKeySpecification;
use jwk\JSONWebKeyPublicKeyUseValues;
use jws\IJWS;
use LaravelDoctrine\ORM\Facades\EntityManager;
use OAuth2\OAuth2Protocol;
use utils\factories\BasicJWTFactory;
use Utils\Services\IAuthService;
use Utils\Services\UtilsServiceCatalog;

/**
 * Class OIDCColdSessionReloadTest
 *
 * Covers the native-app SSO handoff scenario: an id_token minted through a
 * back-channel authorization-code exchange (no browser cookies, as a native
 * app does it) is later presented as id_token_hint on /oauth2/auth from a
 * brand-new cold session. processUserHint() must reload the OP session via
 * the token's jti (AuthService::reloadSession) and authenticate the user
 * without any credential prompt.
 */
final class OIDCColdSessionReloadTest extends OpenStackIDBaseTestCase
{
    const ClientId     = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
    const ClientSecret = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';
    const RedirectUri  = 'https://www.test.com/oauth2';
    const Scope        = 'openid profile email';

    /**
     * @var User
     */
    private $user;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app->singleton(UtilsServiceCatalog::ServerConfigurationService, StubServerConfigurationService::class);
        return $app;
    }

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $user_repository = EntityManager::getRepository(User::class);
        $this->user = $user_repository->findOneBy(['email' => 'sebastian@tipit.net']);
        Session::start();
    }

    /**
     * Point the session facade at a brand-new anonymous session WITHOUT
     * destroying the previous session's server-side data - a real cold
     * browser never touches sessions it does not own (flush/regenerate here
     * would wipe the very session the id_token's jti references).
     */
    private function startColdSession(): void
    {
        Session::save();
        Session::setId(Str::random(40));
        Session::start();
        Auth::logout();
    }

    private function authorizeParams(): array
    {
        return [
            'client_id'     => self::ClientId,
            'redirect_uri'  => self::RedirectUri,
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
            'scope'         => self::Scope,
        ];
    }

    public function testColdSessionReloadFromBackChannelMintedIdTokenHint()
    {
        // Phase A - interactive "browser" session: user logged, consent granted,
        // auth code minted.
        $this->be($this->user);
        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth", $this->authorizeParams());

        $this->assertResponseStatus(302);
        $url = $response->getTargetUrl();
        $this->assertTrue(!str_contains($url, '/auth/login'), 'phase A must not require login');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertTrue(isset($query['code']) && !empty($query['code']), 'phase A must return an auth code');
        $code = $query['code'];

        // Phase B - back-channel exchange on a fresh cookie-less session,
        // exactly as a native app redeems the code.
        $this->startColdSession();

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            [
                'code'         => $code,
                'redirect_uri' => self::RedirectUri,
                'grant_type'   => OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
            ],
            [], [], [],
            ["HTTP_Authorization" => " Basic " . base64_encode(self::ClientId . ':' . self::ClientSecret)]);

        $this->assertResponseStatus(200);
        $json = json_decode($response->getContent());
        $this->assertTrue(!empty($json->id_token), 'exchange must return an id_token');

        // RP-side unwrap: if the id_token is encrypted for the client, decrypt it
        // and use the inner JWS as hint (OIDC Core 3.1.2.1 id_token_hint rules).
        $id_token_hint = $json->id_token;
        $jwt = BasicJWTFactory::build($json->id_token);
        if ($jwt instanceof IJWE) {
            $recipient_key = RSAJWKFactory::build
            (
                new RSAJWKPEMPrivateKeySpecification
                (
                    TestSeeder::$client_private_key_1,
                    RSAJWKPEMPrivateKeySpecification::WithoutPassword,
                    $jwt->getJOSEHeader()->getAlgorithm()->getString()
                )
            );
            $recipient_key->setKeyUse(JSONWebKeyPublicKeyUseValues::Encryption)->setId('recipient_public_key');
            $jwt->setRecipientKey($recipient_key);
            $id_token_hint = $jwt->getPlainText();
            $jwt = BasicJWTFactory::build($id_token_hint);
        }
        $this->assertTrue($jwt instanceof IJWS);

        // Negative control - cold session, no hint: authorize must bounce to login.
        $this->startColdSession();

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth", $this->authorizeParams());

        $this->assertResponseStatus(302);
        $this->assertTrue(str_contains($response->getTargetUrl(), '/auth/login'),
            'cold session without hint must require login');

        // Phase C - cold session WITH the back-channel-minted id_token_hint:
        // reloadSession must authenticate the user and, with former consent on
        // file, redirect straight back with a fresh code. No login prompt.
        $this->startColdSession();

        $params = $this->authorizeParams();
        $params[OAuth2Protocol::OAuth2Protocol_IDTokenHint] = $id_token_hint;

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth", $params);

        $this->assertResponseStatus(302);
        $url = $response->getTargetUrl();
        $this->assertTrue(!str_contains($url, '/auth/login'),
            sprintf('cold session with id_token_hint must not require login, got %s', $url));
        $this->assertTrue(str_starts_with($url, self::RedirectUri),
            sprintf('must redirect back to client, got %s', $url));
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertTrue(isset($query['code']) && !empty($query['code']),
            'cold session with id_token_hint must yield a fresh auth code');

        // The OP session was effectively rebuilt: user is authenticated again.
        $this->assertTrue(Auth::check(), 'reloadSession must leave the user authenticated');
        $this->assertEquals($this->user->getId(), Auth::user()->getId());
    }
}
