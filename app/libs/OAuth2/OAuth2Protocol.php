<?php namespace OAuth2;
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

use App\Http\Utils\UserIPHelperProvider;
use App\Jobs\RevokeUserGrantsOnExplicitLogout;
use Exception;
use Illuminate\Support\Facades\Log;
use jwa\JSONWebSignatureAndEncryptionAlgorithms;
use jwk\impl\JWKSet;
use jwk\impl\RSAJWKFactory;
use jwk\impl\RSAJWKPEMPrivateKeySpecification;
use jwk\JSONWebKeyVisibility;
use jwt\IJWT;
use OAuth2\Discovery\DiscoveryDocumentBuilder;
use OAuth2\Discovery\IOpenIDProviderConfigurationService;
use OAuth2\Endpoints\AuthorizationEndpoint;
use OAuth2\Endpoints\TokenEndpoint;
use OAuth2\Endpoints\TokenIntrospectionEndpoint;
use OAuth2\Endpoints\TokenRevocationEndpoint;
use OAuth2\Exceptions\AbsentClientException;
use OAuth2\Exceptions\AbsentCurrentUserException;
use OAuth2\Exceptions\ExpiredAccessTokenException;
use OAuth2\Exceptions\InvalidClientException;
use OAuth2\Exceptions\InvalidOAuth2Request;
use OAuth2\Exceptions\OAuth2BaseException;
use OAuth2\Exceptions\UriNotAllowedException;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\GrantTypes\AuthorizationCodeGrantType;
use OAuth2\GrantTypes\ClientCredentialsGrantType;
use OAuth2\GrantTypes\HybridGrantType;
use OAuth2\GrantTypes\ImplicitGrantType;
use OAuth2\GrantTypes\PasswordlessGrantType;
use OAuth2\GrantTypes\RefreshBearerTokenGrantType;
use OAuth2\Models\IClient;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Services\ITokenService;
use OAuth2\Repositories\IServerPrivateKeyRepository;
use OAuth2\Requests\OAuth2LogoutRequest;
use OAuth2\Requests\OAuth2Request;
use OAuth2\ResourceServer\IUserService;
use OAuth2\Responses\OAuth2DirectErrorResponse;
use OAuth2\Responses\OAuth2LogoutResponse;
use OAuth2\Responses\OAuth2Response;
use OAuth2\Responses\OAuth2TokenRevocationResponse;
use OAuth2\Services\IApiScopeService;
use OAuth2\Services\IClientJWKSetReader;
use OAuth2\Services\IClientService;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OAuth2\Services\IUserConsentService;
use OAuth2\Strategies\IOAuth2AuthenticationStrategy;
use OAuth2\Strategies\OAuth2IndirectErrorResponseFactoryMethod;
use Utils\ArrayUtils;
use utils\factories\BasicJWTFactory;
use Utils\Services\IAuthService;
use Utils\Services\ICheckPointService;
use Utils\Services\ILogService;
use Utils\Services\IServerConfigurationService;

/**
 * Class OAuth2Protocol
 * Implementation of @see http://tools.ietf.org/html/rfc6749
 * @package OAuth2
 */
final class OAuth2Protocol implements IOAuth2Protocol
{

    /**
     * @var OAuth2Request
     */
    private $last_request = null;
    const OAuth2Protocol_Scope_Delimiter = ' ';
    const OAuth2Protocol_ResponseType_Delimiter = ' ';

    const OAuth2Protocol_GrantType_AuthCode = 'authorization_code';
    const OAuth2Protocol_GrantType_Passwordless = 'passwordless';
    const OAuth2Protocol_GrantType_Implicit = 'implicit';
    const OAuth2Protocol_GrantType_Hybrid = 'hybrid';

    const OAuth2Protocol_GrantType_ResourceOwner_Password = 'password';
    const OAuth2Protocol_GrantType_ClientCredentials = 'client_credentials';
    const OAuth2Protocol_GrantType_RefreshToken = 'refresh_token';

    const OAuth2Protocol_ResponseType_Code = 'code';
    const OAuth2Protocol_ResponseType_OTP = 'otp';
    const OAuth2Protocol_ResponseType_Token = 'token';
    const OAuth2Protocol_ResponseType_IdToken = 'id_token';
    const OAuth2Protocol_ResponseType_None = 'none';

    /**
     * The OAuth 2.0 specification allows for registration of space-separated response_type parameter values. If a
     * Response Type contains one of more space characters (%20), it is compared as a space-delimited list of values
     * in which the order of values does not matter.
     */
    const OAuth2Protocol_ResponseType = 'response_type';
    /**
     * http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html#ResponseModes
     * Informs the Authorization Server of the mechanism to be used for returning Authorization Response parameters from
     * the Authorization Endpoint. This use of this parameter is NOT RECOMMENDED with a value that specifies the same
     * Response Mode as the default Response Mode for the Response Type used.
     */
    const OAuth2Protocol_ResponseMode = 'response_mode';

    /**
     * In this mode, Authorization Response parameters are encoded in the query string added to the redirect_uri when
     * redirecting back to the Client.
     */
    const OAuth2Protocol_ResponseMode_Query = 'query';

    /**
     * In this mode, Authorization Response parameters are encoded in the fragment added to the redirect_uri when
     * redirecting back to the Client.
     */
    const OAuth2Protocol_ResponseMode_Fragment = 'fragment';

    /**
     * http://openid.net/specs/oauth-v2-form-post-response-mode-1_0.html
     * In this mode, Authorization Response parameters are encoded as HTML form values that are auto-submitted in the
     * User Agent, and thus are transmitted via the HTTP POST method to the Client, with the result parameters being
     * encoded in the body using the application/x-www-form-urlencoded format. The action attribute of the form MUST be
     * the Client's Redirection URI. The method of the form attribute MUST be POST. Because the Authorization Response
     * is intended to be used only once, the Authorization Server MUST instruct the User Agent (and any intermediaries)
     * not to store or reuse the content of the response.
     */
    const OAuth2Protocol_ResponseMode_FormPost = 'form_post';

    const OAuth2Protocol_ResponseMode_Direct = 'direct';


    static public $valid_response_modes = array
    (
        self::OAuth2Protocol_ResponseMode_Query,
        self::OAuth2Protocol_ResponseMode_Fragment,
        self::OAuth2Protocol_ResponseMode_FormPost,
        self::OAuth2Protocol_ResponseMode_Direct
    );

    /**
     * http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html#ResponseModes
     *
     * Each Response Type value also defines a default Response Mode mechanism to be used,
     * if no Response Mode is specified using the request parameter.
     * For purposes of this specification, the default Response Mode for the OAuth 2.0 code Response Type is the query
     * encoding. For purposes of this specification, the default Response Mode for the OAuth 2.0 token Response Type is
     * the fragment encoding.
     *
     * @param array $response_type
     * @return string
     */
    static public function getDefaultResponseMode(array $response_type)
    {

        if (count(array_diff($response_type, array(self::OAuth2Protocol_ResponseType_Code))) === 0)
            return self::OAuth2Protocol_ResponseMode_Query;

        if (count(array_diff($response_type, array(self::OAuth2Protocol_ResponseType_Token))) === 0)
            return self::OAuth2Protocol_ResponseMode_Fragment;
        // http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html#Combinations
        if (count(array_diff($response_type, array
                (
                    self::OAuth2Protocol_ResponseType_Code,
                    self::OAuth2Protocol_ResponseType_Token
                )
            )) === 0)
            return self::OAuth2Protocol_ResponseMode_Fragment;

        if (count(array_diff($response_type, array
                (
                    self::OAuth2Protocol_ResponseType_Code,
                    self::OAuth2Protocol_ResponseType_IdToken
                )
            )) === 0)
            return self::OAuth2Protocol_ResponseMode_Fragment;

        if (count(array_diff($response_type, array
                (
                    self::OAuth2Protocol_ResponseType_Token,
                    self::OAuth2Protocol_ResponseType_IdToken
                )
            )) === 0)
            return self::OAuth2Protocol_ResponseMode_Fragment;

        if (count(array_diff($response_type, array
                (
                    self::OAuth2Protocol_ResponseType_Code,
                    self::OAuth2Protocol_ResponseType_Token,
                    self::OAuth2Protocol_ResponseType_IdToken
                )
            )) === 0)
            return self::OAuth2Protocol_ResponseMode_Fragment;
        return null;
    }


    const OAuth2Protocol_ClientId = 'client_id';
    const OAuth2Protocol_UserId = 'user_id';
    const OAuth2Protocol_ClientSecret = 'client_secret';
    const OAuth2Protocol_Token = 'token';
    const OAuth2Protocol_TokenType = 'token_type';

    // http://tools.ietf.org/html/rfc7009#section-2.1
    const OAuth2Protocol_TokenType_Hint = 'token_type_hint';
    const OAuth2Protocol_AccessToken_ExpiresIn = 'expires_in';
    const OAuth2Protocol_RefreshToken = 'refresh_token';
    const OAuth2Protocol_AccessToken = 'access_token';
    const OAuth2Protocol_RedirectUri = 'redirect_uri';
    const OAuth2Protocol_Scope = 'scope';
    const OAuth2Protocol_Audience = 'audience';
    const OAuth2Protocol_State = 'state';

    // passwordless

    const OAuth2PasswordlessConnection = 'connection';
    const OAuth2PasswordlessConnectionSMS = 'sms';
    const OAuth2PasswordlessConnectionEmail = 'email';
    const OAuth2PasswordlessConnectionInline = 'inline';

    const ValidOAuth2PasswordlessConnectionValues = [
        self::OAuth2PasswordlessConnectionSMS,
        self::OAuth2PasswordlessConnectionEmail,
        self::OAuth2PasswordlessConnectionInline,
    ];

    const OAuth2PasswordlessSend = 'send';
    const OAuth2PasswordlessSendCode = 'code';
    const OAuth2PasswordlessSendLink = 'link';

    const ValidOAuth2PasswordlessSendValues = [
        self::OAuth2PasswordlessSendCode,
        self::OAuth2PasswordlessSendLink,
    ];

    const OAuth2PasswordlessEmail = 'email';
    const OAuth2PasswordlessPhoneNumber = 'phone_number';

    /**
     * @see http://openid.net/specs/openid-connect-session-1_0.html#CreatingUpdatingSessions
     * In OpenID Connect, the session at the RP typically starts when the RP validates the End-User's ID Token. Refer
     * to the OpenID Connect Core 1.0 [OpenID.Core] specification to find out how to obtain an ID Token and validate it.
     * When the OP supports session management, it MUST also return the Session State as an additional session_state
     * parameter in the Authentication Response. The OpenID Connect Authentication Response is specified in
     * Section 3.1.2.5 of OpenID Connect Core 1.0.
     * JSON string that represents the End-User's login state at the OP. It MUST NOT contain the space (" ") character.
     * This value is opaque to the RP. This is REQUIRED if session management is supported.
     */
    const OAuth2Protocol_Session_State = 'session_state';
    // http://openid.net/specs/openid-connect-core-1_0.html#TokenResponse
    // ID Token value associated with the authenticated session.
    const OAuth2Protocol_IdToken = 'id_token';

    // http://openid.net/specs/openid-connect-core-1_0.html#AuthRequest
    const OAuth2Protocol_Nonce = 'nonce';

    /**
     * custom param - social login
     */
    const OAuth2Protocol_Provider = 'provider';

    /**
     * Time when the End-User authentication occurred. Its value is a JSON number representing the number of seconds
     * from 1970-01-01T0:0:0Z as measured in UTC until the date/time. When a max_age request is made or when auth_time
     * is requested as an Essential Claim, then this Claim is REQUIRED; otherwise, its inclusion is OPTIONAL.
     * (The auth_time Claim semantically corresponds to the OpenID 2.0 PAPE [OpenID.PAPE] auth_time response parameter.)
     */
    const OAuth2Protocol_AuthTime = 'auth_time';

    /**
     * Access Token hash value. Its value is the base64url encoding of the left-most half of the hash of the octets of
     * the ASCII representation of the access_token value, where the hash algorithm used is the hash algorithm used in
     * the alg Header Parameter of the ID Token's JOSE Header. For instance, if the alg is RS256, hash the access_token
     * value with SHA-256, then take the left-most 128 bits and base64url encode them. The at_hash value is a case
     * sensitive string.
     */
    const OAuth2Protocol_AccessToken_Hash = 'at_hash';

    /**
     * Code hash value. Its value is the base64url encoding of the left-most half of the hash of the octets of the ASCII
     * representation of the code value, where the hash algorithm used is the hash algorithm used in the alg Header
     * Parameter of the ID Token's JOSE Header. For instance, if the alg is HS512, hash the code value with SHA-512,
     * then take the left-most 256 bits and base64url encode them. The c_hash value is a case sensitive string.
     * If the ID Token is issued from the Authorization Endpoint with a code, which is the case for the response_type
     * values code id_token and code id_token token, this is REQUIRED; otherwise, its inclusion is OPTIONAL.
     */
    const OAuth2Protocol_AuthCode_Hash = 'c_hash';

    /**
     * Specifies how the Authorization Server displays the authentication and consent user interface pages to
     * the End-User.
     */
    const OAuth2Protocol_Display = 'display';
    /**
     * The Authorization Server SHOULD display the authentication and consent UI consistent with a full User Agent page
     * view. If the display parameter is not specified, this is the default display mode.
     * The Authorization Server MAY also attempt to detect the capabilities of the User Agent and present an
     * appropriate display.
     */
    const OAuth2Protocol_Display_Page = 'page';
    /**
     * The Authorization Server SHOULD display the authentication and consent UI consistent with a popup User Agent
     * window. The popup User Agent window should be of an appropriate size for a login-focused dialog and should not
     * obscure the entire window that it is popping up over.
     */
    const OAuth2Protocol_Display_PopUp = 'popup';
    /**
     * The Authorization Server SHOULD display the authentication and consent UI consistent with a device that leverages
     * a touch interface.
     */
    const OAuth2Protocol_Display_Touch = 'touch';
    /**
     * The Authorization Server SHOULD display the authentication and consent UI consistent with a "feature phone"
     * type display.
     */
    const OAuth2Protocol_Display_Wap = 'wap';

    /**
     *  Extension: display the login/consent interaction like a json doc
     */
    const OAuth2Protocol_Display_Native = 'native';

    /**
     * @var array
     */
    static public $valid_display_values = array
    (
        self::OAuth2Protocol_Display_Page,
        self::OAuth2Protocol_Display_PopUp,
        self::OAuth2Protocol_Display_Touch,
        self::OAuth2Protocol_Display_Wap,
        self::OAuth2Protocol_Display_Native,
    );

    /**
     * Specifies whether the Authorization Server prompts the End-User for reauthentication and consent.
     * The prompt parameter can be used by the Client to make sure that the End-User is still present for the current
     * session or to bring attention to the request. If this parameter contains none with any other value, an error is
     * returned.
     */
    const OAuth2Protocol_Prompt = 'prompt';

    /**
     * The Authorization Server MUST NOT display any authentication or consent user interface pages. An error is
     * returned if an End-User is not already authenticated or the Client does not have pre-configured consent for the
     * requested Claims or does not fulfill other conditions for processing the request. The error code will typically
     * be login_required, interaction_required, or another code defined in Section 3.1.2.6. This can be used as a method
     * to check for existing authentication and/or consent.
     */
    const OAuth2Protocol_Prompt_None = 'none';

    /**
     * The Authorization Server SHOULD prompt the End-User for reauthentication. If it cannot reauthenticate the
     * End-User, it MUST return an error, typically login_required.
     */
    const OAuth2Protocol_Prompt_Login = 'login';

    /**
     * The Authorization Server SHOULD prompt the End-User for consent before returning information to the Client.
     * If it cannot obtain consent, it MUST return an error, typically consent_required.
     */
    const OAuth2Protocol_Prompt_Consent = 'consent';

    /**
     * The Authorization Server SHOULD prompt the End-User to select a user account. This enables an End-User who has
     * multiple accounts at the Authorization Server to select amongst the multiple accounts that they might have
     * current sessions for. If it cannot obtain an account selection choice made by the End-User, it MUST return an
     * error, typically account_selection_required.
     */
    const OAuth2Protocol_Prompt_SelectAccount = 'select_account';

    /**
     * @var array
     */
    static public $valid_prompt_values = array
    (
        self::OAuth2Protocol_Prompt_None,
        self::OAuth2Protocol_Prompt_Login,
        self::OAuth2Protocol_Prompt_Consent,
        self::OAuth2Protocol_Prompt_SelectAccount
    );

    /**
     * @param string $flow
     * @return array
     */
    static public function getValidResponseTypes($flow = 'all')
    {
        $code_flow = [
            //OAuth2 / OIDC
            [
                self::OAuth2Protocol_ResponseType_Code
            ]
        ];

        $implicit_flow = array
        (
            // only for OAuth2
            array
            (
                self::OAuth2Protocol_ResponseType_Token
            ),
            // OIDC
            array
            (
                self::OAuth2Protocol_ResponseType_IdToken
            ),
            array
            (
                self::OAuth2Protocol_ResponseType_IdToken,
                self::OAuth2Protocol_ResponseType_Token
            )
        );

        $hybrid_flow = array
        (
            array
            (
                self::OAuth2Protocol_ResponseType_Code,
                self::OAuth2Protocol_ResponseType_IdToken
            ),
            array
            (
                self::OAuth2Protocol_ResponseType_Code,
                self::OAuth2Protocol_ResponseType_Token
            ),
            array
            (
                self::OAuth2Protocol_ResponseType_Code,
                self::OAuth2Protocol_ResponseType_IdToken,
                self::OAuth2Protocol_ResponseType_Token
            )
        );

        if ($flow === 'all')
            return array_merge
            (
                $code_flow,
                $implicit_flow,
                $hybrid_flow
            );

        if ($flow === OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless)
            return [
                [
                    self::OAuth2Protocol_ResponseType_OTP
                ]
            ];

        if ($flow === OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode)
            return $code_flow;

        if ($flow === OAuth2Protocol::OAuth2Protocol_GrantType_Implicit)
            return $implicit_flow;

        if ($flow === OAuth2Protocol::OAuth2Protocol_GrantType_Hybrid)
            return $hybrid_flow;

        return [];
    }

    /**
     * @see http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html#Terminology
     *
     * The OAuth 2.0 specification allows for registration of space-separated response_type parameter values. If a
     * Response Type contains one of more space characters (%20), it is compared as a space-delimited list of values in
     * which the order of values does not matter.
     *
     * @param array $response_type
     * @param string $flow
     * @return bool
     */
    static public function responseTypeBelongsToFlow(array $response_type, $flow = 'all')
    {
        if
        (
        !in_array
        (
            $flow, [
                OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
                OAuth2Protocol::OAuth2Protocol_GrantType_Implicit,
                OAuth2Protocol::OAuth2Protocol_GrantType_Hybrid,
                OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless,
                'all'
            ]
        )
        )
            return false;

        $flow_response_types = self::getValidResponseTypes($flow);

        foreach ($flow_response_types as $rt) {
            if (count($rt) !== count($response_type)) continue;
            $diff = array_diff($rt, $response_type);
            if (count($diff) === 0) return true;
        }
        return false;
    }

    /**
     * Maximum Authentication Age. Specifies the allowable elapsed time in seconds since the last time the End-User was
     * actively authenticated by the OP. If the elapsed time is greater than this value, the OP MUST attempt to actively
     * re-authenticate the End-User. (The max_age request parameter corresponds to the OpenID 2.0 PAPE [OpenID.PAPE]
     * max_auth_age request parameter.) When max_age is used, the ID Token returned MUST include an auth_time Claim
     * Value.
     */
    const OAuth2Protocol_MaxAge = 'max_age';

    /**
     * End-User's preferred languages and scripts for the user interface, represented as a space-separated list
     * of BCP47 [RFC5646] language tag values, ordered by preference. For instance, the value "fr-CA fr en" represents
     * a preference for French as spoken in Canada, then French (without a region designation), followed by English
     * (without a region designation). An error SHOULD NOT result if some or all of the requested locales are not
     * supported by the OpenID Provider.
     */
    const OAuth2Protocol_UILocales = 'ui_locales';

    /**
     * ID Token previously issued by the Authorization Server being passed as a hint about the End-User's current or
     * past authenticated session with the Client. If the End-User identified by the ID Token is logged in or is logged
     * in by the request, then the Authorization Server returns a positive response; otherwise, it SHOULD return an
     * error, such as login_required. When possible, an id_token_hint SHOULD be present when prompt=none is used and an
     * invalid_request error MAY be returned if it is not; however, the server SHOULD respond successfully when
     * possible, even if it is not present. The Authorization Server need not be listed as an audience of the ID Token
     * when it is used as an id_token_hint value.
     * If the ID Token received by the RP from the OP is encrypted, to use it as an id_token_hint, the Client MUST
     * decrypt the signed ID Token contained within the encrypted ID Token. The Client MAY re-encrypt the signed ID
     * token to the Authentication Server using a key that enables the server to decrypt the ID Token, and use the
     * re-encrypted ID token as the id_token_hint value.
     */
    const OAuth2Protocol_IDTokenHint = 'id_token_hint';

    /**
     * Hint to the Authorization Server about the login identifier the End-User might use to log in (if necessary).
     * This hint can be used by an RP if it first asks the End-User for their e-mail address (or other identifier)
     * and then wants to pass that value as a hint to the discovered authorization service. It is RECOMMENDED that the
     * hint value match the value used for discovery. This value MAY also be a phone number in the format specified for
     * the phone_number Claim. The use of this parameter is left to the OP's discretion.
     */
    const OAuth2Protocol_LoginHint = 'login_hint';

    /**
     * Custom Hint to pass the OTP Value to auto login
     */
    const OAuth2Protocol_OTP_LoginHint = 'otp_login_hint';

    /**
     * Requested Authentication Context Class Reference values. Space-separated string that specifies the acr values
     * that the Authorization Server is being requested to use for processing this Authentication Request, with the
     * values appearing in order of preference. The Authentication Context Class satisfied by the authentication
     * performed is returned as the acr Claim Value, as specified in Section 2. The acr Claim is requested as a
     * Voluntary Claim by this parameter.
     */
    const OAuth2Protocol_ACRValues = 'acr_values';

    /**
     * OPTIONAL. URL to which the RP is requesting that the End-User's User Agent be redirected after a logout has been
     * performed. The value MUST have been previously registered with the OP, either using the post_logout_redirect_uris
     * Registration parameter or via another mechanism. If supplied, the OP SHOULD honor this request following the logout.
     */
    const OAuth2Protocol_PostLogoutRedirectUri = 'post_logout_redirect_uri';

    /**
     * Indicates whether the user should be re-prompted for consent. The default is auto,
     * so a given user should only see the consent page for a given set of scopes the first time
     * through the sequence. If the value is force, then the user sees a consent page even if they
     * previously gave consent to your application for a given set of scopes.
     */
    const OAuth2Protocol_Approval_Prompt = 'approval_prompt';
    const OAuth2Protocol_Approval_Prompt_Force = 'force';
    const OAuth2Protocol_Approval_Prompt_Auto = 'auto';

    /**
     * Indicates whether your application needs to access an API when the user is not present at
     * the browser. This parameter defaults to online. If your application needs to refresh access tokens
     * when the user is not present at the browser, then use offline. This will result in your application
     * obtaining a refresh token the first time your application exchanges an authorization code for a user.
     */
    const OAuth2Protocol_AccessType = 'access_type';
    const OAuth2Protocol_AccessType_Online = 'online';
    const OAuth2Protocol_AccessType_Offline = 'offline';

    const OAuth2Protocol_GrantType = 'grant_type';
    const OAuth2Protocol_Error = 'error';
    const OAuth2Protocol_ErrorDescription = 'error_description';
    const OAuth2Protocol_ErrorUri = 'error_uri';
    const OAuth2Protocol_Error_InvalidRequest = 'invalid_request';
    const OAuth2Protocol_Error_UnauthorizedClient = 'unauthorized_client';
    const OAuth2Protocol_Error_InvalidOTP = 'invalid_otp';
    const OAuth2Protocol_Error_RedirectUriMisMatch = 'redirect_uri_mismatch';
    const OAuth2Protocol_Error_AccessDenied = 'access_denied';
    const OAuth2Protocol_Error_UnsupportedResponseType = 'unsupported_response_type';
    const OAuth2Protocol_Error_InvalidScope = 'invalid_scope';
    const OAuth2Protocol_Error_UnsupportedGrantType = 'unsupported_grant_type';
    const OAuth2Protocol_Error_InvalidGrant = 'invalid_grant';
    //error codes definitions http://tools.ietf.org/html/rfc6749#section-4.1.2.1
    const OAuth2Protocol_Error_ServerError = 'server_error';
    const OAuth2Protocol_Error_TemporallyUnavailable = 'temporally_unavailable';
    //http://tools.ietf.org/html/rfc7009#section-2.2.1
    const OAuth2Protocol_Error_Unsupported_TokenType = 'unsupported_token_type';
    //http://tools.ietf.org/html/rfc6750#section-3-1
    const OAuth2Protocol_Error_InvalidToken = 'invalid_token';
    const OAuth2Protocol_Error_InsufficientScope = 'insufficient_scope';
    const OAuth2Protocol_Error_Session_Cant_Reload = 'session_cant_reload';

    // http://openid.net/specs/openid-connect-core-1_0.html#AuthError

    /**
     * The Authorization Server requires End-User interaction of some form to proceed. This error MAY be returned when
     * the prompt parameter value in the Authentication Request is none, but the Authentication Request cannot be
     * completed without displaying a user interface for End-User interaction.
     */
    const OAuth2Protocol_Error_Interaction_Required = 'interaction_required';

    /**
     * The Authorization Server requires End-User authentication. This error MAY be returned when the prompt parameter
     * value in the Authentication Request is none, but the Authentication Request cannot be completed without
     * displaying a user interface for End-User authentication.
     */
    const OAuth2Protocol_Error_Login_Required = 'login_required';

    /**
     * The End-User is REQUIRED to select a session at the Authorization Server. The End-User MAY be authenticated at
     * the Authorization Server with different associated accounts, but the End-User did not select a session.
     * This error MAY be returned when the prompt parameter value in the Authentication Request is none, but the
     * Authentication Request cannot be completed without displaying a user interface to prompt for a session to use.
     */
    const OAuth2Protocol_Error_Account_Selection_Required = 'account_selection_required';

    /**
     * The Authorization Server requires End-User consent. This error MAY be returned when the prompt parameter value
     * in the Authentication Request is none, but the Authentication Request cannot be completed without displaying a
     * user interface for End-User consent.
     */
    const OAuth2Protocol_Error_Consent_Required = 'consent_required';

    /**
     * The request_uri in the Authorization Request returns an error or contains invalid data
     */
    const OAuth2Protocol_Error_Invalid_RequestUri = 'invalid_request_uri';

    /**
     * The request parameter contains an invalid Request Object.
     */
    const OAuth2Protocol_Error_Invalid_RequestObject = 'invalid_request_object';

    /**
     * The OP does not support use of the request parameter defined in Section 6.
     */
    const OAuth2Protocol_Error_Request_Not_Supported = 'request_not_supported';
    /**
     * The OP does not support use of the request_uri parameter defined in Section 6.
     */
    const OAuth2Protocol_Error_Request_Uri_Not_Supported = 'request_uri_not_supported';

    /**
     * The OP does not support use of the registration parameter defined in Section 7.2.1.
     */
    const OAuth2Protocol_Error_Registration_Not_Supported = 'registration_not_supported';


    const OAuth2Protocol_Error_Invalid_Recipient_Keys = 'invalid_recipient_keys';
    const OAuth2Protocol_Error_Invalid_Server_Keys = 'invalid_server_keys';
    const OAuth2Protocol_Error_Not_Found_Server_Keys = 'not_found_server_keys';


    public static $valid_responses_types = array
    (
        self::OAuth2Protocol_ResponseType_Code => self::OAuth2Protocol_ResponseType_Code,
        self::OAuth2Protocol_ResponseType_Token => self::OAuth2Protocol_ResponseType_Token
    );

    // http://openid.net/specs/openid-connect-core-1_0.html#ClientAuthentication

    const TokenEndpoint_AuthMethod_ClientSecretBasic = 'client_secret_basic';
    const TokenEndpoint_AuthMethod_ClientSecretPost = 'client_secret_post';
    const TokenEndpoint_AuthMethod_ClientSecretJwt = 'client_secret_jwt';
    const TokenEndpoint_AuthMethod_PrivateKeyJwt = 'private_key_jwt';
    const TokenEndpoint_AuthMethod_None = 'none';

    const OAuth2Protocol_ClientAssertionType = 'client_assertion_type';
    const OAuth2Protocol_ClientAssertion = 'client_assertion';

    public static $token_endpoint_auth_methods = array
    (
        self::TokenEndpoint_AuthMethod_ClientSecretBasic,
        self::TokenEndpoint_AuthMethod_ClientSecretPost,
        self::TokenEndpoint_AuthMethod_ClientSecretJwt,
        self::TokenEndpoint_AuthMethod_PrivateKeyJwt,
        // PKCE only
        self::TokenEndpoint_AuthMethod_None,
    );

    const OpenIdConnect_Scope = 'openid';
    const OfflineAccess_Scope = 'offline_access';

    public static $supported_signing_algorithms = array
    (
        // MAC SHA2
        JSONWebSignatureAndEncryptionAlgorithms::HS256,
        JSONWebSignatureAndEncryptionAlgorithms::HS384,
        JSONWebSignatureAndEncryptionAlgorithms::HS512,
        // RSA
        JSONWebSignatureAndEncryptionAlgorithms::RS256,
        JSONWebSignatureAndEncryptionAlgorithms::RS384,
        JSONWebSignatureAndEncryptionAlgorithms::RS512,
        JSONWebSignatureAndEncryptionAlgorithms::PS256,
        JSONWebSignatureAndEncryptionAlgorithms::PS384,
        JSONWebSignatureAndEncryptionAlgorithms::PS512,
        JSONWebSignatureAndEncryptionAlgorithms::None
    );

    public static $supported_signing_algorithms_hmac_sha2 = array
    (
        JSONWebSignatureAndEncryptionAlgorithms::HS256,
        JSONWebSignatureAndEncryptionAlgorithms::HS384,
        JSONWebSignatureAndEncryptionAlgorithms::HS512,
    );

    public static $supported_signing_algorithms_rsa = array
    (
        JSONWebSignatureAndEncryptionAlgorithms::RS256,
        JSONWebSignatureAndEncryptionAlgorithms::RS384,
        JSONWebSignatureAndEncryptionAlgorithms::RS512,
        JSONWebSignatureAndEncryptionAlgorithms::PS256,
        JSONWebSignatureAndEncryptionAlgorithms::PS384,
        JSONWebSignatureAndEncryptionAlgorithms::PS512,
    );

    // https://tools.ietf.org/html/rfc7518#page-12
    public static $supported_key_management_algorithms = array
    (
        JSONWebSignatureAndEncryptionAlgorithms::RSA1_5,
        JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP,
        JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP_256,
        JSONWebSignatureAndEncryptionAlgorithms::Dir,
        JSONWebSignatureAndEncryptionAlgorithms::None,
    );

    // https://tools.ietf.org/html/rfc7518#page-22
    public static $supported_content_encryption_algorithms = array
    (
        JSONWebSignatureAndEncryptionAlgorithms::A128CBC_HS256,
        JSONWebSignatureAndEncryptionAlgorithms::A192CBC_HS384,
        JSONWebSignatureAndEncryptionAlgorithms::A256CBC_HS512,
        JSONWebSignatureAndEncryptionAlgorithms::None,
    );

    /**
     * @see http://tools.ietf.org/html/rfc6749#appendix-A
     * VSCHAR     = %x20-7E
     */
    const VsChar = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz.-_~';

    /**
     * PKCE
     * @see https://tools.ietf.org/html/rfc7636
     **/

    // auth request new params

    const PKCE_CodeChallenge = 'code_challenge';
    const PKCE_CodeChallengeMethod = 'code_challenge_method';

    const PKCE_CodeChallengeMethodPlain = 'plain';
    const PKCE_CodeChallengeMethodSHA256 = 'S256';

    const PKCE_ValidCodeChallengeMethods = [self::PKCE_CodeChallengeMethodPlain, self::PKCE_CodeChallengeMethodSHA256];

    // token request new params

    const PKCE_CodeVerifier = 'code_verifier';

    //services
    /**
     * @var ILogService
     */
    private $log_service;
    /**
     * @var ICheckPointService
     */
    private $checkpoint_service;

    /**
     * @var IClientService
     */
    private $client_service;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var IAuthService
     */
    private $auth_service;

    /**
     * @var IPrincipalService
     */
    private $principal_service;

    /**
     * @var ITokenService
     */
    private $token_service;

    //endpoints
    /**
     * @var AuthorizationEndpoint
     */
    private $authorize_endpoint;
    /**
     * @var TokenEndpoint
     */
    private $token_endpoint;
    /**
     * @var TokenRevocationEndpoint
     */
    private $revoke_endpoint;
    /**
     * @var TokenIntrospectionEndpoint
     */
    private $introspection_endpoint;

    /**
     * grant types
     * @var array
     */
    private $grant_types = [];

    /**
     * @var IServerPrivateKeyRepository
     */
    private $server_private_keys_repository;

    /**
     * @var IOpenIDProviderConfigurationService
     */
    private $oidc_provider_configuration_service;

    /**
     * @var IMementoOAuth2SerializerService
     */
    private $memento_service;

    /**
     * @var IServerConfigurationService
     */
    private $configuration_service;

    /**
     * @param ILogService $log_service
     * @param IClientService $client_service
     * @param IClientRepository $client_repository
     * @param ITokenService $token_service
     * @param IAuthService $auth_service
     * @param IOAuth2AuthenticationStrategy $auth_strategy
     * @param ICheckPointService $checkpoint_service
     * @param IApiScopeService $scope_service
     * @param IUserConsentService $user_consent_service
     * @param IServerPrivateKeyRepository $server_private_keys_repository
     * @param IOpenIDProviderConfigurationService $oidc_provider_configuration_service
     * @param IMementoOAuth2SerializerService $memento_service
     * @param ISecurityContextService $security_context_service
     * @param IPrincipalService $principal_service
     * @param IServerPrivateKeyRepository $server_private_key_repository
     * @param IClientJWKSetReader $jwk_set_reader_service
     * @param UserIPHelperProvider $ip_helper
     * @param IServerConfigurationService $configuration_service
     */
    public function __construct
    (
        ILogService $log_service,
        IClientService $client_service,
        IClientRepository $client_repository,
        ITokenService $token_service,
        IAuthService $auth_service,
        IOAuth2AuthenticationStrategy $auth_strategy,
        ICheckPointService $checkpoint_service,
        IApiScopeService $scope_service,
        IUserConsentService $user_consent_service,
        IServerPrivateKeyRepository $server_private_keys_repository,
        IOpenIDProviderConfigurationService $oidc_provider_configuration_service,
        IMementoOAuth2SerializerService $memento_service,
        ISecurityContextService $security_context_service,
        IPrincipalService $principal_service,
        IServerPrivateKeyRepository $server_private_key_repository,
        IClientJWKSetReader $jwk_set_reader_service,
        UserIPHelperProvider $ip_helper,
        IServerConfigurationService $configuration_service
    )
    {

        $this->server_private_keys_repository = $server_private_keys_repository;
        $this->oidc_provider_configuration_service = $oidc_provider_configuration_service;
        $this->memento_service = $memento_service;

        $passwordless_grant_type = new PasswordlessGrantType
        (
            $scope_service,
            $client_service,
            $client_repository,
            $token_service,
            $auth_service,
            $auth_strategy,
            $log_service,
            $user_consent_service,
            $this->memento_service,
            $security_context_service,
            $principal_service,
            $server_private_key_repository,
            $jwk_set_reader_service
        );

        $authorization_code_grant_type = new AuthorizationCodeGrantType
        (
            $scope_service,
            $client_service,
            $client_repository,
            $token_service,
            $auth_service,
            $auth_strategy,
            $log_service,
            $user_consent_service,
            $this->memento_service,
            $security_context_service,
            $principal_service,
            $server_private_key_repository,
            $jwk_set_reader_service
        );

        $implicit_grant_type = new ImplicitGrantType
        (
            $scope_service,
            $client_service,
            $client_repository,
            $token_service,
            $auth_service,
            $auth_strategy,
            $log_service,
            $user_consent_service,
            $this->memento_service,
            $security_context_service,
            $principal_service,
            $server_private_key_repository,
            $jwk_set_reader_service
        );

        $hybrid_grant_type = new HybridGrantType
        (
            $scope_service,
            $client_service,
            $client_repository,
            $token_service,
            $auth_service,
            $auth_strategy,
            $log_service,
            $user_consent_service,
            $this->memento_service,
            $security_context_service,
            $principal_service,
            $server_private_key_repository,
            $jwk_set_reader_service
        );

        $refresh_bearer_token_grant_type = new RefreshBearerTokenGrantType
        (
            $client_service,
            $client_repository,
            $token_service,
            $log_service
        );

        $client_credential_grant_type = new ClientCredentialsGrantType
        (
            $scope_service,
            $client_service,
            $client_repository,
            $token_service,
            $log_service
        );

        // setting grants collection
        $this->grant_types[$passwordless_grant_type->getType()] = $passwordless_grant_type;
        $this->grant_types[$authorization_code_grant_type->getType()] = $authorization_code_grant_type;
        $this->grant_types[$implicit_grant_type->getType()] = $implicit_grant_type;
        $this->grant_types[$refresh_bearer_token_grant_type->getType()] = $refresh_bearer_token_grant_type;
        $this->grant_types[$client_credential_grant_type->getType()] = $client_credential_grant_type;
        $this->grant_types[$hybrid_grant_type->getType()] = $hybrid_grant_type;

        $this->log_service = $log_service;
        $this->checkpoint_service = $checkpoint_service;
        $this->client_service = $client_service;
        $this->client_repository = $client_repository;
        $this->auth_service = $auth_service;
        $this->principal_service = $principal_service;
        $this->token_service = $token_service;

        $this->authorize_endpoint = new AuthorizationEndpoint($this);
        $this->token_endpoint = new TokenEndpoint($this);
        $this->revoke_endpoint = new TokenRevocationEndpoint($this, $client_service, $client_repository, $token_service, $log_service);
        $this->introspection_endpoint = new TokenIntrospectionEndpoint
        (
            $this,
            $client_service,
            $client_repository,
            $token_service,
            $auth_service,
            $log_service,
            $ip_helper
        );

        $this->configuration_service = $configuration_service;
    }

    /**
     * @param OAuth2Request $request
     * @return OAuth2Response
     * @throws Exception
     * @throws UriNotAllowedException
     */
    public function authorize(OAuth2Request $request = null)
    {
        try {
            $this->last_request = $request;

            if (is_null($this->last_request)) throw new InvalidOAuth2Request;

            if (!$this->last_request->isValid()) {
                // then check if we have a memento ....
                if (!$this->memento_service->exists())
                    throw new InvalidOAuth2Request($this->last_request->getLastValidationError());

                $this->last_request = OAuth2AuthorizationRequestFactory::getInstance()->build
                (
                    OAuth2Message::buildFromMemento($this->memento_service->load())
                );

                if (!$this->last_request->isValid())
                    throw new InvalidOAuth2Request($this->last_request->getLastValidationError());

            }
            return $this->authorize_endpoint->handle($this->last_request);
        } catch (UriNotAllowedException $ex1) {
            $this->log_service->warning($ex1);
            $this->checkpoint_service->trackException($ex1);
            throw $ex1;
        } catch (OAuth2BaseException $ex2) {
            $this->log_service->warning($ex2);
            $this->checkpoint_service->trackException($ex2);

            $redirect_uri = $this->validateRedirectUri($this->last_request);

            if (is_null($redirect_uri))
                throw $ex2;

            return OAuth2IndirectErrorResponseFactoryMethod::buildResponse
            (
                $this->last_request,
                $ex2->getError(),
                $ex2->getMessage(),
                $redirect_uri
            );
        } catch (AbsentClientException $ex3) {
            $this->log_service->warning($ex3);
            $this->checkpoint_service->trackException($ex3);

            $redirect_uri = $this->validateRedirectUri($this->last_request);
            if (is_null($redirect_uri))
                throw $ex3;

            return OAuth2IndirectErrorResponseFactoryMethod::buildResponse
            (
                $this->last_request,
                OAuth2Protocol::OAuth2Protocol_Error_ServerError,
                $ex3->getMessage(),
                $redirect_uri
            );
        } catch (AbsentCurrentUserException $ex4) {
            $this->log_service->warning($ex4);
            $this->checkpoint_service->trackException($ex4);

            $redirect_uri = $this->validateRedirectUri($this->last_request);
            if (is_null($redirect_uri))
                throw $ex4;

            return OAuth2IndirectErrorResponseFactoryMethod::buildResponse
            (
                $this->last_request,
                OAuth2Protocol::OAuth2Protocol_Error_ServerError,
                $ex4->getMessage(),
                $redirect_uri
            );
        } catch (Exception $ex) {
            $this->log_service->error($ex);
            $this->checkpoint_service->trackException($ex);

            $redirect_uri = $this->validateRedirectUri($this->last_request);
            if (is_null($redirect_uri))
                throw $ex;

            return OAuth2IndirectErrorResponseFactoryMethod::buildResponse
            (
                $this->last_request,
                OAuth2Protocol::OAuth2Protocol_Error_ServerError,
                $ex->getMessage(),
                $redirect_uri
            );
        }
    }

    /**
     * @param OAuth2Request $request
     * @return OAuth2Response
     */
    public function token(OAuth2Request $request = null)
    {
        try {
            $this->last_request = $request;

            if (is_null($this->last_request))
                throw new InvalidOAuth2Request;

            if (!$this->last_request->isValid())
                throw new InvalidOAuth2Request($this->last_request->getLastValidationError());

            return $this->token_endpoint->handle($this->last_request);
        } catch (OAuth2BaseException $ex1) {
            $this->log_service->warning($ex1);
            $this->checkpoint_service->trackException($ex1);

            return new OAuth2DirectErrorResponse($ex1->getError(), $ex1->getMessage());
        } catch (Exception $ex) {
            $this->log_service->error($ex);
            $this->checkpoint_service->trackException($ex);

            return new OAuth2DirectErrorResponse
            (
                OAuth2Protocol::OAuth2Protocol_Error_ServerError,
                $ex->getMessage()
            );
        }
    }

    /**
     * Revoke Token Endpoint
     * @see http://tools.ietf.org/html/rfc7009
     * @param OAuth2Request $request
     * @return OAuth2Response
     */
    public function revoke(OAuth2Request $request = null)
    {

        try {
            $this->last_request = $request;

            if (is_null($this->last_request))
                throw new InvalidOAuth2Request;

            if (!$this->last_request->isValid())
                throw new InvalidOAuth2Request($this->last_request->getLastValidationError());

            return $this->revoke_endpoint->handle($this->last_request);
        } catch (Exception $ex) {
            $this->log_service->error($ex);
            $this->checkpoint_service->trackException($ex);
            //simple say "OK" and be on our way ...
            return new OAuth2TokenRevocationResponse;
        }
    }

    /**
     * Introspection Token Endpoint
     * @see http://tools.ietf.org/html/draft-richer-oauth-introspection-04
     * @param OAuth2Request $request
     * @return OAuth2Response
     */
    public function introspection(OAuth2Request $request = null)
    {
        try {
            $this->last_request = $request;

            if (is_null($this->last_request))
                throw new InvalidOAuth2Request;

            if (!$this->last_request->isValid())
                throw new InvalidOAuth2Request($this->last_request->getLastValidationError());

            return $this->introspection_endpoint->handle($this->last_request);
        } catch (ExpiredAccessTokenException $ex1) {
            $this->log_service->warning($ex1);
            return new OAuth2DirectErrorResponse($ex1->getError(), $ex1->getMessage());
        } catch (OAuth2BaseException $ex2) {
            $this->log_service->warning($ex2);
            $this->checkpoint_service->trackException($ex2);
            return new OAuth2DirectErrorResponse($ex2->getError(), $ex2->getMessage());
        } catch (Exception $ex) {
            $this->log_service->error($ex);
            $this->checkpoint_service->trackException($ex);

            return new OAuth2DirectErrorResponse
            (
                OAuth2Protocol::OAuth2Protocol_Error_InvalidRequest,
                OAuth2Protocol::OAuth2Protocol_Error_InvalidRequest
            );
        }
    }

    public function getAvailableGrants()
    {
        return $this->grant_types;
    }

    /**
     * @param IClient $client
     * @return bool
     */
    static public function isClientAllowedToUseTokenEndpointAuth(IClient $client)
    {
        return $client->getClientType() === IClient::ClientType_Confidential ||
            $client->getApplicationType() === IClient::ApplicationType_Native;
    }

    static public function getTokenEndpointAuthMethodsPerClientType(IClient $client)
    {
        if ($client->getClientType() == IClient::ClientType_Public) {
            return ArrayUtils::convert2Assoc
            (
                array
                (
                    self::TokenEndpoint_AuthMethod_PrivateKeyJwt,
                    self::TokenEndpoint_AuthMethod_None
                )
            );
        }

        return ArrayUtils::convert2Assoc
        (
            array_merge
            (
                self::$token_endpoint_auth_methods,
                array
                (
                    self::TokenEndpoint_AuthMethod_None
                )
            )
        );
    }

    /**
     * @param IClient $client
     * @return array
     */
    static public function getSigningAlgorithmsPerClientType(IClient $client)
    {
        if ($client->getClientType() == IClient::ClientType_Public) {
            return ArrayUtils::convert2Assoc
            (
                array_merge
                (
                    self::$supported_signing_algorithms_rsa,
                    array
                    (
                        JSONWebSignatureAndEncryptionAlgorithms::None
                    )
                )
            );
        }
        return ArrayUtils::convert2Assoc
        (
            array_merge
            (
                self::$supported_signing_algorithms_hmac_sha2,
                self::$supported_signing_algorithms_rsa,
                array
                (
                    JSONWebSignatureAndEncryptionAlgorithms::None
                )
            )
        );
    }


    /**
     * @param IClient $client
     * @return array
     */
    static public function getKeyManagementAlgorithmsPerClientType(IClient $client)
    {
        if ($client->getClientType() == IClient::ClientType_Public) {
            return ArrayUtils::convert2Assoc
            (
                array_diff
                (
                    self::$supported_key_management_algorithms,
                    array
                    (
                        JSONWebSignatureAndEncryptionAlgorithms::Dir
                    )
                )
            );
        }
        return ArrayUtils::convert2Assoc
        (
            self::$supported_key_management_algorithms
        );
    }


    /**
     * @return string
     */
    public function getJWKSDocument()
    {
        $keys = $this->server_private_keys_repository->getActives();
        $set = [];

        foreach ($keys as $private_key) {
            $jwk = RSAJWKFactory::build
            (
                new RSAJWKPEMPrivateKeySpecification
                (
                    $private_key->getPEM(),
                    $private_key->getPassword()
                )
            );

            $jwk->setVisibility(JSONWebKeyVisibility::PublicOnly);

            $jwk
                ->setId($private_key->getKeyId())
                ->setKeyUse($private_key->getUse())
                ->setType($private_key->getType())
                ->setAlgorithm($private_key->getAlg()->getName());

            array_push($set, $jwk);
        }

        $jkws = new JWKSet($set);
        return $jkws->toJson();
    }

    /**
     * @see http://openid.net/specs/openid-connect-discovery-1_0.html
     * @return string
     */
    public function getDiscoveryDocument():string
    {
        $builder = new DiscoveryDocumentBuilder();

        return $builder
            ->setIssuer($this->oidc_provider_configuration_service->getIssuerUrl())
            ->setAuthEndpoint($this->oidc_provider_configuration_service->getAuthEndpoint())
            ->setTokenEndpoint($this->oidc_provider_configuration_service->getTokenEndpoint())
            ->setUserInfoEndpoint($this->oidc_provider_configuration_service->getUserInfoEndpoint())
            ->setJWKSUrl($this->oidc_provider_configuration_service->getJWKSUrl())
            ->setRevocationEndpoint($this->oidc_provider_configuration_service->getRevocationEndpoint())
            ->setIntrospectionEndpoint($this->oidc_provider_configuration_service->getIntrospectionEndpoint())
            // session management http://openid.net/specs/openid-connect-session-1_0.html
            ->setEndSessionEndpoint($this->oidc_provider_configuration_service->getEndSessionEndpoint())
            ->setCheckSessionIframe($this->oidc_provider_configuration_service->getCheckSessionIFrame())
            // response types
            ->addResponseTypeSupported('code')
            ->addResponseTypeSupported('token')
            ->addResponseTypeSupported('code token')
            ->addResponseTypeSupported('token id_token')
            ->addResponseTypeSupported('code token id_token')
            // claims
            ->addClaimSupported('aud')
            ->addClaimSupported('exp')
            ->addClaimSupported('iat')
            ->addClaimSupported('iss')
            ->addClaimSupported('sub')
            ->addClaimSupported(StandardClaims::Email)
            ->addClaimSupported(StandardClaims::EmailVerified)
            ->addClaimSupported(StandardClaims::Name)
            ->addClaimSupported(StandardClaims::GivenName)
            ->addClaimSupported(StandardClaims::FamilyName)
            ->addClaimSupported(StandardClaims::NickName)
            ->addClaimSupported(StandardClaims::Picture)
            ->addClaimSupported(StandardClaims::Birthdate)
            ->addClaimSupported(StandardClaims::Locale)
            ->addClaimSupported(StandardClaims::Gender)
            ->addClaimSupported(StandardClaims::Address)
            // scopes
            ->addScopeSupported(self::OpenIdConnect_Scope)
            ->addScopeSupported(IUserService::UserProfileScope_Address)
            ->addScopeSupported(IUserService::UserProfileScope_Email)
            ->addScopeSupported(IUserService::UserProfileScope_Profile)
            // id token signing alg
            ->addIdTokenSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::HS256)
            ->addIdTokenSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::HS384)
            ->addIdTokenSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::HS512)
            ->addIdTokenSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RS256)
            ->addIdTokenSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RS384)
            ->addIdTokenSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RS512)
            ->addIdTokenSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::PS256)
            ->addIdTokenSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::PS384)
            ->addIdTokenSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::PS512)
            // id token enc alg
            ->addIdTokenEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RSA1_5)
            ->addIdTokenEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP)
            ->addIdTokenEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP_256)
            ->addIdTokenEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::Dir)
            // id token enc enc
            ->addIdTokenEncryptionEncSupported(JSONWebSignatureAndEncryptionAlgorithms::A128CBC_HS256)
            ->addIdTokenEncryptionEncSupported(JSONWebSignatureAndEncryptionAlgorithms::A192CBC_HS384)
            ->addIdTokenEncryptionEncSupported(JSONWebSignatureAndEncryptionAlgorithms::A256CBC_HS512)
            // user info signing alg
            ->addUserInfoSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::HS256)
            ->addUserInfoSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::HS384)
            ->addUserInfoSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::HS512)
            ->addUserInfoSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RS256)
            ->addUserInfoSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RS384)
            ->addUserInfoSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RS512)
            ->addUserInfoSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::PS256)
            ->addUserInfoSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::PS384)
            ->addUserInfoSigningAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::PS512)
            // user info enc alg
            ->addUserInfoEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RSA1_5)
            ->addUserInfoEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP)
            ->addUserInfoEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP_256)
            ->addUserInfoEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::Dir)
            // user info enc enc
            ->addUserInfoEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::A128CBC_HS256)
            ->addUserInfoEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::A192CBC_HS384)
            ->addUserInfoEncryptionAlgSupported(JSONWebSignatureAndEncryptionAlgorithms::A256CBC_HS512)
            ->addSubjectTypeSupported(IClient::SubjectType_Public)
            ->addSubjectTypeSupported(IClient::SubjectType_Pairwise)
            ->addTokenEndpointAuthMethodSupported(self::TokenEndpoint_AuthMethod_ClientSecretBasic)
            ->addTokenEndpointAuthMethodSupported(self::TokenEndpoint_AuthMethod_ClientSecretPost)
            ->addTokenEndpointAuthMethodSupported(self::TokenEndpoint_AuthMethod_PrivateKeyJwt)
            ->addTokenEndpointAuthMethodSupported(self::TokenEndpoint_AuthMethod_ClientSecretJwt)
            ->addResponseModeSupported(self::OAuth2Protocol_ResponseMode_FormPost)
            ->addResponseModeSupported(self::OAuth2Protocol_ResponseMode_Query)
            ->addResponseModeSupported(self::OAuth2Protocol_ResponseMode_Fragment)
            ->addDisplayValueSupported(self::OAuth2Protocol_Display_Page)
            ->addDisplayValueSupported(self::OAuth2Protocol_Display_PopUp)
            ->addDisplayValueSupported(self::OAuth2Protocol_Display_Touch)
            ->addDisplayValueSupported(self::OAuth2Protocol_Display_Wap)
            ->addDisplayValueSupported(self::OAuth2Protocol_Display_Native)
            ->addAvailableThirdPartyIdentityProviders()
            ->addAllowsNativeAuth()
            ->addAllowsOTPAuth()
            ->render();
    }

    /**
     * @see http://openid.net/specs/openid-connect-session-1_0.html#RPLogout
     * @param OAuth2Request $request
     * @return OAuth2Response
     */
    public function endSession(OAuth2Request $request = null)
    {
        try {
            $this->log_service->debug_msg("OAuth2Protocol::endSession");

            $this->last_request = $request;

            if (is_null($this->last_request)) {
                $this->log_service->debug_msg("OAuth2Protocol::endSession last request is null");
                throw new InvalidOAuth2Request;
            }

            if (!$this->last_request->isValid()) {
                $this->log_service->debug_msg(sprintf("OAuth2Protocol::endSession last request is invalid error %s", $this->last_request->getLastValidationError()));
                throw new InvalidOAuth2Request($this->last_request->getLastValidationError());
            }

            if (!$this->last_request instanceof OAuth2LogoutRequest) throw new InvalidOAuth2Request;

            $id_token_hint = $this->last_request->getIdTokenHint();
            $client_id = $this->last_request->getClientId();
            $user_id = null;
            $user = null;

            if (!empty($id_token_hint)) {
                $jwt = BasicJWTFactory::build($id_token_hint);

                if ((!$jwt instanceof IJWT)) {
                    $this->log_service->debug_msg("OAuth2Protocol::endSession invalid id_token_hint!");
                    throw new InvalidOAuth2Request('invalid id_token_hint!');
                }


                $jwt_client_id = $jwt->getClaimSet()->getAudience()->getString();
                $jwt_issuer = $jwt->getClaimSet()->getIssuer()->getString();
                $current_issuer = $this->configuration_service->getSiteUrl();

                if($jwt_issuer != $current_issuer){
                    throw new InvalidOAuth2Request(sprintf("issuer provided on id_token_hint (%s) differs from current one %s",
                        $jwt_issuer, $current_issuer));
                }
                $user_id = $jwt->getClaimSet()->getSubject();

                if (!empty($client_id) && $client_id != $jwt_client_id) {
                        throw new InvalidOAuth2Request(sprintf("client id provided on id_token_hint (%s) differs from current one %s",
                            $jwt_client_id, $client_id));
                }

                $client_id = $jwt_client_id;
            }

            if (is_null($client_id)) {
                $this->log_service->debug_msg("OAuth2Protocol::endSession client_id can not be inferred.");
                throw new InvalidClientException('client_id can not be inferred.');
            }

            $client = $this->client_repository->getClientById($client_id);

            if (is_null($client)) {
                $this->log_service->debug_msg(sprintf("OAuth2Protocol::endSession client %s not found.", $client_id));
                throw new InvalidClientException('Client not found.');
            }

            $redirect_logout_uri = $this->last_request->getPostLogoutRedirectUri();
            $state = $this->last_request->getState();

            if (!empty($redirect_logout_uri) && !$client->isPostLogoutUriAllowed($redirect_logout_uri)) {
                $this->log_service->debug_msg(sprintf("OAuth2Protocol::endSession post_logout_redirect_uri %s not allowed.", $redirect_logout_uri));
                throw new InvalidOAuth2Request('post_logout_redirect_uri not allowed!');
            }

            if (!is_null($user_id)) {
                // try to get the user from id token ( if its set )
                $user_id = $this->auth_service->unwrapUserId(intval($user_id->getString()));
                $user = $this->auth_service->getUserById($user_id);

                if(is_null($user)){
                   Log::warning(sprintf("OAuth2Protocol::endSession user hint not found (%s)", $user_id));
                }
            }

            // get current user
            $logged_user = $this->auth_service->getCurrentUser();

            if(!is_null($logged_user) && !is_null($user) && $logged_user->getId() !== $user->getId()) {
                Log::warning
                (
                    sprintf
                    (
                        "OAuth2Protocol::endSession user does not match with current session! logged user id %s - user id %s",
                        $logged_user->getId(),
                        $user->getId()
                    )
                );
            }

            /*
            if(!is_null($user)){
                RevokeUserGrantsOnExplicitLogout::dispatch($user, $client_id)->afterResponse();
            }
            */

            if (!is_null($logged_user)) {
                $this->auth_service->logout();
            }

            if (!empty($redirect_logout_uri)) {
                return new OAuth2LogoutResponse($redirect_logout_uri, $state);
            }

            return null;
        } catch (UriNotAllowedException $ex1) {
            $this->log_service->warning($ex1);
            $this->checkpoint_service->trackException($ex1);
            return new OAuth2DirectErrorResponse(OAuth2Protocol::OAuth2Protocol_Error_UnauthorizedClient);
        } catch (OAuth2BaseException $ex2) {
            $this->log_service->warning($ex2);
            $this->checkpoint_service->trackException($ex2);

            return new OAuth2DirectErrorResponse($ex2->getError(), $ex2->getMessage());
        } catch (Exception $ex) {
            $this->log_service->error($ex);
            $this->checkpoint_service->trackException($ex);

            return new OAuth2DirectErrorResponse
            (
                OAuth2Protocol::OAuth2Protocol_Error_ServerError,
                $ex->getMessage()
            );
        }
    }

    /**
     * @return OAuth2Request
     */
    public function getLastRequest()
    {
        return $this->last_request;
    }

    /**
     * @param OAuth2Request|null $request
     * @return null
     */
    private function validateRedirectUri(OAuth2Request $request = null)
    {
        if (is_null($request))
            return null;
        $redirect_uri = $request->getRedirectUri();
        if (is_null($redirect_uri))
            return null;
        $client_id = $request->getClientId();
        if (is_null($client_id))
            return null;
        $client = $this->client_repository->getClientById($client_id);
        if (is_null($client))
            return null;
        if (!$client->isUriAllowed($redirect_uri))
            return null;
        return $redirect_uri;
    }
}