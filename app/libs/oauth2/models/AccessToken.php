<?php

namespace oauth2\models;

use Zend\Math\Rand;

class AccessToken extends Token {

    private $auth_code;
    private $redirect_uri;

    public function __construct(){
        parent::__construct(Token::DefaultByteLength);
    }

    public static function create(AuthorizationCode $auth_code, $redirect_uri, $lifetime = 3600){
        $instance = new self();
        $instance->value        = Rand::getString($instance->len,null,true);
        $instance->scope        = $auth_code->getScope();
        $instance->redirect_uri = $redirect_uri;
        $instance->client_id    = $auth_code->getClientId();
        $instance->auth_code    = $auth_code->getValue();
        $instance->lifetime     = $lifetime;
        return $instance;
    }

    public static function load($value,AuthorizationCode $auth_code, $issued, $redirect_uri = null,$lifetime = 3600){
        $instance = new self();
        $instance->value        = $value;
        $instance->scope        = $auth_code->getScope();
        $instance->redirect_uri = $redirect_uri;
        $instance->client_id    = $auth_code->getClientId();
        $instance->auth_code    = $auth_code->getValue();
        $instance->issued       = $issued;
        $instance->lifetime     = $lifetime;
        return $instance;
    }

    public function getAuthCode(){
        return $this->auth_code;
    }

    public function getRedirectUri(){
        return $this->redirect_uri;
    }


    public function toJSON(){
        return '{}';
    }

    public function fromJSON($json)
    {
        // TODO: Implement fromJSON() method.
    }
} 