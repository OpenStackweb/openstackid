<?php namespace OAuth2\Discovery;
use App\libs\Auth\SocialLoginProviders;
use Illuminate\Support\Facades\Config;

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

/**
 * Class DiscoveryDocumentBuilder
 * @package OAuth2\Discovery
 */
final class DiscoveryDocumentBuilder
{
    /**
     * @var array
     */
    private $set = [];

    /**
     * @param string $issuer
     * @return $this
     */
    public function setIssuer($issuer)
    {
        $this->set[OpenIDProviderMetadata::Issuer] = $issuer;
        return $this;
    }

    /**
     * @param string $auth_endpoint
     * @return $this
     */
    public function setAuthEndpoint($auth_endpoint)
    {
        $this->set[OpenIDProviderMetadata::AuthEndpoint] = $auth_endpoint;
        return $this;
    }

    /**
     * @param string $token_endpoint
     * @return $this
     */
    public function setTokenEndpoint($token_endpoint)
    {
        $this->set[OpenIDProviderMetadata::TokenEndpoint] = $token_endpoint;
        return $this;
    }

    /**
     * @param string $user_info_endpoint
     * @return $this
     */
    public function setUserInfoEndpoint($user_info_endpoint)
    {
        $this->set[OpenIDProviderMetadata::UserInfoEndpoint] = $user_info_endpoint;
        return $this;
    }

    /**
     * @param string $end_session_endpoint
     * @return $this
     */
    public function setEndSessionEndpoint($end_session_endpoint)
    {
        $this->set[OpenIDProviderMetadata::EndSessionEndPoint] = $end_session_endpoint;
        return $this;
    }

    /**
     * @param string $check_session_iframe
     * @return $this
     */
    public function setCheckSessionIframe($check_session_iframe)
    {
        $this->set[OpenIDProviderMetadata::CheckSessionIFrame] = $check_session_iframe;
        return $this;
    }

    /**
     * @param string $jwks_url
     * @return $this
     */
    public function setJWKSUrl($jwks_url)
    {
        $this->set[OpenIDProviderMetadata::JWKSUrl] = $jwks_url;
        return $this;
    }

    /**
     * @param string $revocation_endpoint
     * @return $this
     */
    public function setRevocationEndpoint($revocation_endpoint)
    {
        $this->set[OpenIDProviderMetadata::RevocationEndpoint] = $revocation_endpoint;
        return $this;
    }

    /**
     * @param string $introspection_endpoint
     * @return $this
     */
    public function setIntrospectionEndpoint($introspection_endpoint)
    {
        $this->set[OpenIDProviderMetadata::IntrospectionEndpoint] = $introspection_endpoint;
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     */
    private function addArrayValue($key, $value)
    {

        if( !isset($this->set[$key]))
            $this->set[$key] = [];

        array_push($this->set[$key], $value);
    }

    /**
     * @param string $response_type
     * @return $this
     */
    public function addResponseTypeSupported($response_type)
    {
        $this->addArrayValue(OpenIDProviderMetadata::ResponseTypesSupported, $response_type);
        return $this;
    }

    /**
     * @param string $display_value
     * @return $this
     */
    public function addDisplayValueSupported($display_value){
        $this->addArrayValue(OpenIDProviderMetadata::DisplayValuesSupported, $display_value);
        return $this;
    }

    /**
     * @param string $response_mode
     * @return $this
     */
    public function addResponseModeSupported($response_mode)
    {
        $this->addArrayValue(OpenIDProviderMetadata::ResponseModesSupported, $response_mode);
        return $this;
    }

    /**
     * @param string $subject_type
     * @return $this
     */
    public function addSubjectTypeSupported($subject_type)
    {
        $this->addArrayValue(OpenIDProviderMetadata::SubjectTypesSupported, $subject_type);
        return $this;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function addScopeSupported($scope){
        $this->addArrayValue(OpenIDProviderMetadata::ScopesSupported, $scope);
        return $this;
    }

    /**
     * @param string $claim
     * @return $this
     */
    public function addClaimSupported($claim)
    {
        $this->addArrayValue(OpenIDProviderMetadata::ClaimsSupported, $claim);
        return $this;
    }

    /**
     * @param string $auth_method
     * @return $this
     */
    public function addTokenEndpointAuthMethodSupported($auth_method)
    {
        $this->addArrayValue(OpenIDProviderMetadata::TokenEndpointAuthMethodsSupported, $auth_method);
        return $this;
    }

    /**
     * @param string $alg
     * @return $this
     */
    public function addIdTokenSigningAlgSupported($alg)
    {
        $this->addArrayValue(OpenIDProviderMetadata::IdTokenSigningAlgValuesSupported, $alg);
        return $this;
    }

    /**
     * @param string $alg
     * @return $this
     */
    public function addIdTokenEncryptionAlgSupported($alg)
    {
        $this->addArrayValue(OpenIDProviderMetadata::IdTokenEncryptionAlgValuesSupported, $alg);
        return $this;
    }

    /**
     * @param string $enc
     * @return $this
     */
    public function addIdTokenEncryptionEncSupported($enc)
    {
        $this->addArrayValue(OpenIDProviderMetadata::IdTokenEncryptionEncValuesSupported, $enc);
        return $this;
    }

    /**
     * @param string $alg
     * @return $this
     */
    public function addUserInfoSigningAlgSupported($alg)
    {
        $this->addArrayValue(OpenIDProviderMetadata::UserInfoSigningAlgValuesSupported, $alg);
        return $this;
    }

    /**
     * @param string $alg
     * @return $this
     */
    public function addUserInfoEncryptionAlgSupported($alg)
    {
        $this->addArrayValue(OpenIDProviderMetadata::UserInfoEncryptionAlgValuesSupported, $alg);
        return $this;
    }

    /**
     * @param string $enc
     * @return $this
     */
    public function addUserInfoEncryptionEncSupported($enc)
    {
        $this->addArrayValue(OpenIDProviderMetadata::UserInfoEncryptionEncValuesSupported, $enc);
        return $this;
    }

    /**
     * @return $this
     */
    public function addAvailableThirdPartyIdentityProviders(){
        foreach(SocialLoginProviders::ValidProviders as $provider)
            if(SocialLoginProviders::isEnabledProvider($provider))
                $this->addArrayValue("third_party_identity_providers", $provider);
        return $this;
    }

    public function addAllowsNativeAuth(){
        $this->set["allows_native_auth"] = boolval(Config::get("auth.allows_native_on_config", 1));
        return $this;
    }

    public function addAllowsOTPAuth(){
        $this->set["allows_otp_auth"] = boolval(Config::get("auth.allows_opt_auth", 1));
        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        $json = json_encode($this->set);
        $json = str_replace('\/','/', $json);
        return $json;
    }
}