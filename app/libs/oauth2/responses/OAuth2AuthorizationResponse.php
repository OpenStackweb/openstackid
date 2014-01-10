<?php

namespace oauth2\responses;
use oauth2\OAuth2Protocol;

/**
 * Class OAuth2AuthorizationResponse
 * http://tools.ietf.org/html/rfc6749#section-4.1.2
 * @package oauth2\responses
 */
class OAuth2AuthorizationResponse extends OAuth2IndirectResponse {


    public function __construct($return_url, $code, $state=null)
    {
        parent::__construct();
        $this[OAuth2Protocol::OAuth2Protocol_ResponseType_Code]  = $code;
        $this->setReturnTo($return_url);
        if(!is_null($state))
            $this[OAuth2Protocol::OAuth2Protocol_State] = $state;
    }

}