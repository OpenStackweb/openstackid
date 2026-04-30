<?php
namespace App\Http\Controllers\OAuth2;
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

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use OAuth2\Exceptions\OAuth2BaseException;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\IOAuth2Protocol;
use OAuth2\OAuth2Message;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Requests\OAuth2AccessTokenValidationRequest;
use OAuth2\Requests\OAuth2LogoutRequest;
use OAuth2\Requests\OAuth2TokenRequest;
use OAuth2\Requests\OAuth2TokenRevocationRequest;
use OAuth2\Responses\OAuth2Response;
use OAuth2\Strategies\OAuth2ResponseStrategyFactoryMethod;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Utils\Http\HttpContentType;
use Utils\Services\IAuthService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Class OAuth2ProviderController
 */
final class OAuth2ProviderController extends Controller
{
    /**
     * @var IOAuth2Protocol
     */
    private $oauth2_protocol;

    /**
     * @var IAuthService
     */
    private $auth_service;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @param IOAuth2Protocol $oauth2_protocol
     * @param IClientRepository $client_repository
     * @param IAuthService $auth_service
     */
    public function __construct
    (
        IOAuth2Protocol $oauth2_protocol,
        IClientRepository $client_repository,
        IAuthService $auth_service
    ) {
        $this->oauth2_protocol = $oauth2_protocol;
        $this->auth_service = $auth_service;
        $this->client_repository = $client_repository;
    }

    /**
     * Authorize HTTP Endpoint
     * The authorization server MUST support the use of the HTTP "GET"
     * method [RFC2616] for the authorization endpoint and MAY support the
     * use of the "POST" method as well.
     * @return mixed
     */
    #[OA\Get(
        path: '/oauth2/auth',
        operationId: 'oauth2AuthorizeGet',
        summary: 'OAuth2 Authorization Endpoint (GET)',
        description: 'Initiates an OAuth2 authorization flow. Supports Authorization Code, Implicit, Hybrid, and OpenID Connect flows. Per RFC 6749 §3.1, GET is required.',
        tags: ['OAuth2 / OpenID Connect'],
        parameters: [
            new OA\Parameter(name: 'response_type', in: 'query', required: true, description: 'The OAuth 2.0 specification allows for registration of space-separated response_type parameter values. If a Response Type contains one of more space characters (%20), it is compared as a space-delimited list of values in which the order of values does not matter. Possible values are: code, token, id_token, otp, none. The "none" value cannot be used with any other response type value.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'client_id', in: 'query', required: true, description: 'OAuth2 client identifier', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'redirect_uri', in: 'query', required: true, description: 'Redirect URI', schema: new OA\Schema(type: 'string', format: 'uri')),
            new OA\Parameter(name: 'scope', in: 'query', required: false, description: 'Space-delimited scopes', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'state', in: 'query', required: false, description: 'Opaque state parameter', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'approval_prompt', in: 'query', required: false, description: 'Indicates whether the user should be re-prompted for consent. The default is auto, so a given user should only see the consent page for a given set of scopes the first time through the sequence. If the value is force, then the user sees a consent page even if they previously gave consent to your application for a given set of scopes.', enum: ['auto', 'force'], schema: new OA\Schema(type: 'string', enum: ['auto', 'force'])),
            new OA\Parameter(name: 'access_type', in: 'query', required: false, description: 'Indicates whether your application needs to access an API when the user is not present at the browser. This parameter defaults to online. If your application needs to refresh access tokens when the user is not present at the browser, then use offline. This will result in your application obtaining a refresh token the first time your application exchanges an authorization code for a user.', schema: new OA\Schema(type: 'string', enum: ['online', 'offline'])),
            new OA\Parameter(name: 'response_mode', in: 'query', required: false, description: 'OPTIONAL. Informs the Authorization Server of the mechanism to be used for returning Authorization Response parameters from the Authorization Endpoint. This use of this parameter is NOT RECOMMENDED with a value that specifies the same Response Mode as the default Response Mode for the Response Type used.\nThe default Response Mode for the OAuth 2.0 code Response Type is the query encoding. For purposes of this specification, the default Response Mode for the OAuth 2.0 token Response Type is the fragment encoding.', schema: new OA\Schema(type: 'string', enum: ['query', 'fragment', 'form_post', 'direct'])),
            new OA\Parameter(name: 'code_challenge', in: 'query', required: false, description: 'PKCE code challenge', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'code_challenge_method', in: 'query', required: false, description: 'Optional. PKCE challenge method', schema: new OA\Schema(type: 'string', enum: ['plain', 'S256'])),
            new OA\Parameter(name: 'display', in: 'query', required: false, description: 'UI display preference (OIDC)', schema: new OA\Schema(type: 'string', enum: ['page', 'popup', 'touch', 'wap', 'native'])),
            new OA\Parameter(name: 'tenant', in: 'query', required: false, description: 'Tenant identifier', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Authorization request processed (response in body), depends on "response_mode" param'),
            new OA\Response(response: HttpResponse::HTTP_FOUND, description: 'Redirect to client redirect_uri with authorization code, tokens, or error'),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Bad Request', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    #[OA\Post(
        path: '/oauth2/auth',
        operationId: 'oauth2AuthorizePost',
        summary: 'OAuth2 Authorization Endpoint (POST)',
        description: 'Initiates an OAuth2 authorization flow via POST. Same parameters as GET but sent as form data.',
        tags: ['OAuth2 / OpenID Connect'],
        requestBody: new OA\RequestBody(
            description: 'Authorization request parameters',
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(ref: '#/components/schemas/OAuth2AuthorizationRequest')
            )
        ),
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Authorization request processed (response in body), depends on "response_mode" param'),
            new OA\Response(response: HttpResponse::HTTP_FOUND, description: 'Redirect to client redirect_uri with authorization code, tokens, or error'),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Bad Request', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    #[OA\Put(
        path: '/oauth2/auth',
        operationId: 'oauth2AuthorizePut',
        summary: 'OAuth2 Authorization Endpoint (PUT)',
        description: 'Initiates an OAuth2 authorization flow via PUT. Same parameters as GET but sent as form data.',
        tags: ['OAuth2 / OpenID Connect'],
        requestBody: new OA\RequestBody(
            description: 'Authorization request parameters',
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(ref: '#/components/schemas/OAuth2AuthorizationRequest')
            )
        ),
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Authorization request processed (response in body), depends on "response_mode" param'),
            new OA\Response(response: HttpResponse::HTTP_FOUND, description: 'Redirect to client redirect_uri with authorization code, tokens, or error'),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Bad Request', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/oauth2/auth',
        operationId: 'oauth2AuthorizePatch',
        summary: 'OAuth2 Authorization Endpoint (PATCH)',
        description: 'Initiates an OAuth2 authorization flow via PATCH. Same parameters as GET but sent as form data.',
        tags: ['OAuth2 / OpenID Connect'],
        requestBody: new OA\RequestBody(
            description: 'Authorization request parameters',
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(ref: '#/components/schemas/OAuth2AuthorizationRequest')
            )
        ),
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Authorization request processed (response in body), depends on "response_mode" param'),
            new OA\Response(response: HttpResponse::HTTP_FOUND, description: 'Redirect to client redirect_uri with authorization code, tokens, or error'),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Bad Request', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    #[OA\Delete(
        path: '/oauth2/auth',
        operationId: 'oauth2AuthorizeDelete',
        summary: 'OAuth2 Authorization Endpoint (DELETE)',
        description: 'Initiates an OAuth2 authorization flow via DELETE. Same parameters as GET but sent as form data.',
        tags: ['OAuth2 / OpenID Connect'],
        requestBody: new OA\RequestBody(
            description: 'Authorization request parameters',
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(ref: '#/components/schemas/OAuth2AuthorizationRequest')
            )
        ),
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Authorization request processed (response in body), depends on "response_mode" param'),
            new OA\Response(response: HttpResponse::HTTP_FOUND, description: 'Redirect to client redirect_uri with authorization code, tokens, or error'),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Bad Request', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    public function auth()
    {
        try {
            $response = $this->oauth2_protocol->authorize
            (
                OAuth2AuthorizationRequestFactory::getInstance()->build
                (
                    new OAuth2Message
                    (
                        Request::all()
                    )
                )
            );

            if ($response instanceof OAuth2Response) {
                $strategy = OAuth2ResponseStrategyFactoryMethod::buildStrategy
                (
                    $this->oauth2_protocol->getLastRequest(),
                    $response
                );
                return $strategy->handle($response);
            }

            return $response;
        } catch (OAuth2BaseException $ex1) {
            $payload = [
                'error' => $ex1->getError(),
                'error_description' => $ex1->getMessage()
            ];
            if (request()->isJson()) {
                return Response::json($payload, 400);
            }
            return Response::view
            (
                'errors.400',
                $payload,
                400
            );
        } catch (Exception $ex) {
            Log::error($ex);
            $payload = [
                'error' => "Bad Request",
                'error_description' => "Generic Error"
            ];
            if (request()->isJson()) {
                return Response::json($payload, 400);
            }
            return Response::view
            (
                'errors.400',
                $payload,
                400
            );
        }
    }

    /**
     * Token HTTP Endpoint
     * @return mixed
     */
    #[OA\Post(
        path: '/oauth2/token',
        operationId: 'oauth2Token',
        summary: 'OAuth2 Token Endpoint',
        description: 'Issues access tokens. Supports authorization_code, client_credentials, password, refresh_token, and passwordless grant types.',
        tags: ['OAuth2 / OpenID Connect'],
        requestBody: new OA\RequestBody(
            description: 'Token request parameters',
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(ref: '#/components/schemas/OAuth2TokenRequest')
            )
        ),
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Successful token response', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2TokenResponse')),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Token error', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    public function token()
    {

        $response = $this->oauth2_protocol->token
        (
            new OAuth2TokenRequest
            (
                new OAuth2Message
                (
                    Request::all()
                )
            )
        );

        if ($response instanceof OAuth2Response) {
            $strategy = OAuth2ResponseStrategyFactoryMethod::buildStrategy
            (
                $this->oauth2_protocol->getLastRequest(),
                $response
            );
            return $strategy->handle($response);
        }

        return $response;
    }

    /**
     * Revoke Token HTTP Endpoint
     * @return mixed
     */
    #[OA\Post(
        path: '/oauth2/token/revoke',
        operationId: 'oauth2RevokeToken',
        summary: 'OAuth2 Token Revocation Endpoint',
        description: 'Revokes an access token or refresh token per RFC 7009. The endpoint is idempotent — revoking a non-existent token returns success.',
        tags: ['OAuth2 / OpenID Connect'],
        security: [['OAuth2ProviderClientBasic' => []], ['OAuth2ProviderSecurity' => []]],
        requestBody: new OA\RequestBody(
            description: 'Token revocation parameters',
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(ref: '#/components/schemas/OAuth2TokenRevocationRequest')
            )
        ),
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Token successfully revoked'),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Revocation error', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    public function revoke()
    {
        $response = $this->oauth2_protocol->revoke
        (
            new OAuth2TokenRevocationRequest
            (
                new OAuth2Message
                (
                    Request::all()
                )
            )
        );

        if ($response instanceof OAuth2Response) {
            $strategy = OAuth2ResponseStrategyFactoryMethod::buildStrategy
            (
                $this->oauth2_protocol->getLastRequest(),
                $response
            );
            return $strategy->handle($response);
        }

        return $response;
    }

    /**
     * @see http://tools.ietf.org/html/draft-richer-oauth-introspection-04
     * Introspection Token HTTP Endpoint
     * @return mixed
     */
    #[OA\Post(
        path: '/oauth2/token/introspection',
        operationId: 'oauth2IntrospectToken',
        summary: 'OAuth2 Token Introspection Endpoint',
        description: 'Validates and returns metadata about an access token per RFC 7662. Returns detailed information about the token including associated user data.',
        tags: ['OAuth2 / OpenID Connect'],
        security: [['OAuth2ProviderClientBasic' => []], ['OAuth2ProviderSecurity' => []]],
        requestBody: new OA\RequestBody(
            description: 'Token introspection parameters',
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(ref: '#/components/schemas/OAuth2TokenIntrospectionRequest')
            )
        ),
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Token introspection response', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2IntrospectionResponse')),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Introspection error', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    public function introspection()
    {

        $response = $this->oauth2_protocol->introspection
        (
            new OAuth2AccessTokenValidationRequest
            (
                new OAuth2Message
                (
                    Request::all()
                )
            )
        );

        if ($response instanceof OAuth2Response) {
            $strategy = OAuth2ResponseStrategyFactoryMethod::buildStrategy
            (
                $this->oauth2_protocol->getLastRequest(),
                $response
            );
            return $strategy->handle($response);
        }

        return $response;
    }

    /**
     *  OP's JSON Web Key Set [JWK] document.
     * @return string
     */
    #[OA\Get(
        path: '/oauth2/certs',
        operationId: 'oauth2GetCerts',
        summary: 'JSON Web Key Set (JWKS) Endpoint',
        description: 'Returns the OpenID Provider JSON Web Key Set document containing public keys used for signing tokens (RFC 7517).',
        tags: ['OAuth2 / OpenID Connect'],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'JWKS document', content: new OA\JsonContent(ref: '#/components/schemas/JWKSResponse')),
        ]
    )]
    public function certs()
    {

        $doc = $this->oauth2_protocol->getJWKSDocument();
        $response = Response::make($doc, 200);
        $response->header('Content-Type', HttpContentType::Json);

        return $response;
    }

    #[OA\Get(
        path: '/.well-known/openid-configuration',
        operationId: 'oauth2Discovery',
        summary: 'OpenID Connect Discovery Endpoint',
        description: 'Returns the OpenID Provider Configuration document per OpenID Connect Discovery 1.0.',
        tags: ['OAuth2 / OpenID Connect'],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'OpenID Connect Discovery document', content: new OA\JsonContent(ref: '#/components/schemas/OpenIDDiscoveryResponse')),
        ]
    )]
    public function discovery()
    {

        $doc = $this->oauth2_protocol->getDiscoveryDocument();
        $response = Response::make($doc, 200);
        $response->header('Content-Type', HttpContentType::Json);

        return $response;
    }

    /**
     * @see http://openid.net/specs/openid-connect-session-1_0.html#OPiframe
     */
    public function checkSessionIFrame()
    {
        $data = [];
        return View::make("oauth2.session.check-session", $data);
    }

    /**
     * @see http://openid.net/specs/openid-connect-session-1_0.html#RPLogout
     */
    #[OA\Get(
        path: '/oauth2/end-session',
        operationId: 'oauth2EndSessionGet',
        summary: 'OpenID Connect End Session Endpoint (GET)',
        description: 'Initiates RP-Initiated Logout per OpenID Connect Session Management 1.0. Terminates the user session and optionally redirects to the post-logout URI.',
        tags: ['OAuth2 / OpenID Connect'],
        parameters: [
            new OA\Parameter(name: 'client_id', in: 'query', required: true, description: 'OAuth2 client identifier', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id_token_hint', in: 'query', required: false, description: 'Previously issued ID token', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'post_logout_redirect_uri', in: 'query', required: false, description: 'URI to redirect after logout (must be registered)', schema: new OA\Schema(type: 'string', format: 'uri')),
            new OA\Parameter(name: 'state', in: 'query', required: false, description: 'Opaque state parameter returned in the redirect', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_FOUND, description: 'Redirect to post_logout_redirect_uri'),
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Session ended page (HTML)'),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Invalid logout request', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    #[OA\Post(
        path: '/oauth2/end-session',
        operationId: 'oauth2EndSessionPost',
        summary: 'OpenID Connect End Session Endpoint (POST)',
        description: 'Initiates RP-Initiated Logout via POST. Same parameters as GET but sent as form data.',
        tags: ['OAuth2 / OpenID Connect'],
        requestBody: new OA\RequestBody(
            description: 'End session parameters',
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(ref: '#/components/schemas/OAuth2EndSessionRequest')
            )
        ),
        responses: [
            new OA\Response(response: HttpResponse::HTTP_FOUND, description: 'Redirect to post_logout_redirect_uri'),
            new OA\Response(response: HttpResponse::HTTP_OK, description: 'Session ended page (HTML)'),
            new OA\Response(response: HttpResponse::HTTP_BAD_REQUEST, description: 'Invalid logout request', content: new OA\JsonContent(ref: '#/components/schemas/OAuth2ErrorResponse')),
        ]
    )]
    public function endSession()
    {
        $request = new OAuth2LogoutRequest
        (
            new OAuth2Message
            (
                Request::all()
            )
        );

        if (!$request->isValid()) {
            Log::error(sprintf('invalid OAuth2LogoutRequest %s', $request->getLastValidationError()));
            return Response::view('errors.400', [
                'error' => 'Invalid logout request.',
                'error_description' => $request->getLastValidationError()
            ], 400);
        }

        $response = $this->oauth2_protocol->endSession($request);

        if ($response instanceof OAuth2Response) {
            $strategy = OAuth2ResponseStrategyFactoryMethod::buildStrategy($request, $response);
            return $strategy->handle($response);
        }

        return View::make('oauth2.session.session-ended');
    }
}