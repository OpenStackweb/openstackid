<?php namespace OpenId\Models;
use Auth\User;

/**
 * Interface ITrustedSite
 * @package openid\model
 */
interface ITrustedSite {
    /**
     * @return string
     */
    public function getRealm():string;

    public function getData();

    public function getUser():User;

    public function getAuthorizationPolicy():string;

    public function getUITrustedData();
}