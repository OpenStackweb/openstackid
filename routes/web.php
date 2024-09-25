<?php
/**
 * Copyright 2021 OpenStack Foundation
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
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// openid endpoints
Route::group(array('middleware' => ['ssl']), function () {

    Route::get('/', "HomeController@index");

    // OpenId endpoints

    Route::group(['namespace' => 'OpenId'], function () {

        Route::get('/discovery', "DiscoveryController@idp");
        Route::get("/discovery/users/{identifier}", "DiscoveryController@user")->where(array('identifier' => '[\d\w\.\#]+'));
        //op endpoint url
        Route::post('/accounts/openid2', 'OpenIdProviderController@endpoint');
        Route::get('/accounts/openid2', 'OpenIdProviderController@endpoint');
    });

    //user interaction
    Route::group(array('prefix' => 'auth'), function () {

        Route::group(array('prefix' => 'login'), function () {
            Route::get('', "UserController@getLogin");
            Route::post('account-verify', [ 'middleware' => ['csrf'], 'uses' => 'UserController@getAccount']);
            Route::post('otp', ['middleware' => ['csrf'], 'uses' => 'UserController@emitOTP']);
            Route::post('', ['middleware' => 'csrf', 'uses' => 'UserController@postLogin']);
            Route::get('cancel', "UserController@cancelLogin");
            Route::group(array('prefix' => '{provider}'), function () {
                Route::get('', 'SocialLoginController@redirect')->name("social_login");
                Route::any('callback','SocialLoginController@callback')->name("social_login_callback");
            });
        });

        // registration routes
        Route::group(array('prefix' => 'register'), function () {
            Route::get('', 'Auth\RegisterController@showRegistrationForm');
            Route::post('', ['middleware' => ['csrf'], 'uses' => 'Auth\RegisterController@register']);
        });

        Route::group(array('prefix' => 'verification'), function () {
            Route::get('', 'Auth\EmailVerificationController@showVerificationForm');
            Route::get('{token}', 'Auth\EmailVerificationController@verify')->name("verification_verify");
            Route::post('', ['middleware' => 'csrf', 'uses' => 'Auth\EmailVerificationController@resend']);
        });

        // password reset routes

        Route::group(array('prefix' => 'password'), function () {
            Route::group(array('prefix' => 'set'), function () {
                Route::get('{token}', 'Auth\PasswordSetController@showPasswordSetForm')->name('password.set');
                Route::post('', ['middleware' => 'csrf', 'uses' => 'Auth\PasswordSetController@setPassword']);
            });

            Route::group(array('prefix' => 'reset'), function () {
                Route::get('', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
                Route::get('{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
                Route::post('', ['middleware' => 'csrf', 'uses' => 'Auth\ResetPasswordController@reset']);
            });

            Route::post('email', ['middleware' => 'csrf', 'uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail'])->name('password.email');
        });

    });

    /*
    * If the Claimed Identifier was not previously discovered by the Relying Party
    * (the "openid.identity" in the request was "http://specs.openid.net/auth/2.0/identifier_select"
    * or a different Identifier, or if the OP is sending an unsolicited positive assertion),
    * the Relying Party MUST perform discovery on the Claimed Identifier in
    * the response to make sure that the OP is authorized to make assertions about the Claimed Identifier.
    */
    Route::get("/{identifier}", "UserController@getIdentity");
});

//oauth2 endpoints

Route::group(['namespace' => 'OAuth2', 'middleware' => ['ssl']], function () {
    Route::get('/.well-known/openid-configuration', "OAuth2ProviderController@discovery");
});

Route::group(['namespace' => 'OAuth2', 'prefix' => 'oauth2', 'middleware' => ['ssl']], function () {

    Route::get('/check-session', "OAuth2ProviderController@checkSessionIFrame");
    Route::get('/end-session', "OAuth2ProviderController@endSession");
    Route::post('/end-session', "OAuth2ProviderController@endSession");

    //authorization endpoint
    Route::any('/auth', "OAuth2ProviderController@auth");
    // OIDC
    // certificates
    Route::get('/certs', "OAuth2ProviderController@certs");
    // discovery document
    Route::get('/.well-known/openid-configuration', "OAuth2ProviderController@discovery");
    //token endpoint
    Route::group(array('prefix' => 'token'), function () {
        Route::post('/', "OAuth2ProviderController@token");
        Route::post('/revoke', "OAuth2ProviderController@revoke");
        Route::post('/introspection', "OAuth2ProviderController@introspection");
    });
});

Route::group(array('middleware' => ['ssl', 'auth']), function () {
    Route::get('/accounts/user/consent', "UserController@getConsent");
    Route::post('/accounts/user/consent', ['middleware' => 'csrf', 'uses' => 'UserController@postConsent']);
    Route::any("/accounts/user/logout", "UserController@logout");
    Route::get("/accounts/user/profile", "UserController@getProfile");
    Route::any("/accounts/user/profile/trusted_site/delete/{id}", "UserController@deleteTrustedSite");
});

Route::group(['prefix' => 'admin', 'middleware' => ['ssl', 'auth']], function () {
    //client admin UI
    Route::get('clients/edit/{id}', ['middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'AdminController@editRegisteredClient']);
    Route::get('clients', 'AdminController@listOAuth2Clients');
    Route::get('/grants', 'AdminController@editIssuedGrants');

    //oauth2 server admin UI
    Route::group(['middleware' => ['oauth2.currentuser.serveradmin']], function () {
        Route::get('/api-scope-groups', 'AdminController@listApiScopeGroups');
        Route::get('/api-scope-groups/{id}', 'AdminController@editApiScopeGroup');
        Route::get('/resource-servers', 'AdminController@listResourceServers');
        Route::get('/resource-server/{id}', 'AdminController@editResourceServer');
        Route::get('/api/{id}', 'AdminController@editApi');
        Route::get('/scope/{id}', 'AdminController@editScope');
        Route::get('/endpoint/{id}', 'AdminController@editEndpoint');
        Route::get('/locked-clients', 'AdminController@listLockedClients');
        // server private keys
        Route::get('/private-keys', 'AdminController@listServerPrivateKeys');
        //security
        Route::group(array('prefix' => 'users'), function () {
            Route::get('', 'AdminController@listUsers');
            Route::group(array('prefix' => '{user_id}'), function () {
                Route::get('', 'AdminController@editUser')->name("edit_user");
            });
        });

        Route::group(array('prefix' => 'groups'), function () {
            Route::get('', 'AdminController@listGroups');
            Route::group(array('prefix' => '{group_id}'), function () {
                Route::get('', 'AdminController@editGroup');
            });
        });
    });

    Route::group(array('middleware' => ['openstackid.currentuser.serveradmin']), function () {
        Route::get('server-config', 'AdminController@listServerConfig');
        Route::post('server-config', 'AdminController@saveServerConfig');
        Route::get('banned-ips', 'AdminController@listBannedIPs');
    });
});

// Admin Backend Services

Route::group([
    'namespace' => 'Api',
    'prefix' => 'admin/api/v1',
    'middleware' => ['ssl', 'auth']], function () {

    Route::group(['prefix' => 'users'], function () {

        Route::get('', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' =>"UserApiController@getAll"]);
        Route::post('', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => "UserApiController@create"]);

        Route::group(['prefix' => 'me'], function () {
            Route::delete('tokens/{value}', "UserApiController@revokeMyToken");
            Route::put('', "UserApiController@updateMe");
            Route::put('pic',  "UserApiController@updateMyPic");
            Route::get('actions', "UserActionApiController@getActionsByCurrentUser");
        });

        Route::get('access-tokens', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => 'ClientApiController@getAllAccessTokens']);
        Route::get('actions', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => 'UserActionApiController@getActions']);

        Route::group(['prefix' => '{id}'], function () {

            Route::group(['prefix' => 'locked'], function () {
                Route::put('', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => 'UserApiController@unlock']);
                Route::delete('', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => 'UserApiController@lock']);
            });

            Route::get('', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => "UserApiController@get"]);
            Route::delete('', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => "UserApiController@delete"]);
            Route::put('', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => "UserApiController@update"]);
            Route::put('pic', ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => "UserApiController@updatePic"]);
            Route::group(['prefix' => 'access-tokens'], function () {
                Route::delete('{value}',  ['middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => "UserApiController@revokeToken"]);
            });
        });

    });

    Route::group(['prefix' => 'groups', 'middleware' => ['openstackid.currentuser.serveradmin.json']], function () {
        Route::get('', "GroupApiController@getAll");
        Route::post('', "GroupApiController@create");
        Route::group(['prefix' => '{id}'], function () {
            Route::get('', "GroupApiController@get");
            Route::delete('', "GroupApiController@delete");
            Route::put('', "GroupApiController@update");
            Route::group(['prefix' => 'users'], function () {
                Route::get('', "GroupApiController@getUsersFromGroup");
                Route::group(['prefix' => '{user_id}'], function () {
                    Route::put('', 'GroupApiController@addUserToGroup');
                    Route::delete('', 'GroupApiController@removeUserFromGroup');
                });
            });
        });
    });

    Route::group(['prefix' => 'banned-ips', 'middleware' => ['openstackid.currentuser.serveradmin.json']], function () {
        Route::get('/', "ApiBannedIPController@getAll");
        Route::group(['prefix' => '{id?}'], function () {
            Route::get('', "ApiBannedIPController@get");
            Route::delete('', "ApiBannedIPController@delete");
        });
    });

    //client api
    Route::group(array('prefix' => 'clients'), function () {

        Route::get('', 'ClientApiController@getAll');
        Route::post('', 'ClientApiController@create');

        Route::group(['prefix' => '{id}'], function () {
            Route::get('', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => "ClientApiController@get"));
            Route::put('', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@update'));
            Route::delete('', array('middleware' => ['oauth2.currentuser.owns.client'], 'uses' => 'ClientApiController@delete'));
            // particular settings

            Route::delete('lock', array('middleware' => ['openstackid.currentuser.serveradmin.json'], 'uses' => 'ClientApiController@unlock'));
            Route::put('secret', array('middleware' => ['oauth2.currentuser.owns.client'], 'uses' => 'ClientApiController@regenerateClientSecret'));
            Route::put('use-refresh-tokens/{use_refresh_token}', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@setRefreshTokenClient'));
            Route::put('rotate-refresh-tokens/{rotate_refresh_token}', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@setRotateRefreshTokenPolicy'));
            Route::get('access-tokens', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@getAccessTokens'));
            Route::get('refresh-tokens', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@getRefreshTokens'));

            // public keys
            Route::group(['prefix' => 'public_keys'], function () {
                Route::post('', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientPublicKeyApiController@_create'));
                Route::get('', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientPublicKeyApiController@getAll'));
                Route::group(['prefix' => '{public_key_id}'], function () {
                    Route::delete('', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientPublicKeyApiController@_delete'));
                    Route::put('', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientPublicKeyApiController@_update'));
                });
            });
            //allowed redirect uris endpoints
            Route::group(['prefix' => 'uris'], function () {
                Route::get('', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@getRegisteredUris'));
                Route::post('', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@addAllowedRedirectUri'));
                Route::delete('{uri_id}', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@deleteClientAllowedUri'));
            });

            // allowed origins
            Route::group(['prefix' => 'origins'], function () {
                Route::post('', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@addAllowedOrigin'));
                Route::delete('{origin_id}', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@deleteClientAllowedOrigin'));

            });

            Route::delete('token/{value}/{hint}', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@revokeToken'));
            // scopes
            Route::group(['prefix' => 'scopes'], function () {
                Route::put('{scope_id}', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@addAllowedScope'));
                Route::delete('{scope_id}', array('middleware' => ['oauth2.currentuser.allow.client.edition'], 'uses' => 'ClientApiController@removeAllowedScope'));
            });

            Route::put('active', array('middleware' => ['oauth2.currentuser.owns.client'], 'uses' => 'ClientApiController@activate'));
            Route::delete('active', array('middleware' => ['oauth2.currentuser.owns.client'], 'uses' => 'ClientApiController@deactivate'));
        });

        Route::group(['prefix' => 'me'], function () {
            Route::get('access-tokens', array('middleware' => [], 'uses' => 'ClientApiController@getAccessTokensByCurrentUser'));
            Route::get('refresh-tokens', array('middleware' => [], 'uses' => 'ClientApiController@getRefreshTokensByCurrentUser'));
        });
    });

    // resource servers
    Route::group(array('prefix' => 'resource-servers', 'middleware' => ['oauth2.currentuser.serveradmin.json']), function () {

        Route::get('', "ApiResourceServerController@getAll");
        Route::post('', "ApiResourceServerController@create");

        Route::group(['prefix' => '{id}'], function () {
            Route::get('', "ApiResourceServerController@get");
            Route::delete('', "ApiResourceServerController@delete");
            Route::put('', "ApiResourceServerController@update");
            Route::put('client-secret', "ApiResourceServerController@regenerateClientSecret");
            Route::put('active', "ApiResourceServerController@activate");
            Route::delete('active', "ApiResourceServerController@deactivate");
        });
    });

    // api scope groups
    Route::group(['prefix' => 'api-scope-groups', 'middleware' => ['oauth2.currentuser.serveradmin.json']], function () {
        Route::get('', "ApiScopeGroupController@getAll");
        Route::post('', "ApiScopeGroupController@create");

        Route::group(['prefix' => '{id}'], function () {
            Route::put('', "ApiScopeGroupController@update");
            Route::get('', "ApiScopeGroupController@get");
            Route::delete('', "ApiScopeGroupController@delete");
            Route::put('/active', "ApiScopeGroupController@activate");
            Route::delete('/active', "ApiScopeGroupController@deactivate");
        });

    });

    // apis
    Route::group(['prefix' => 'apis', 'middleware' => ['oauth2.currentuser.serveradmin.json']], function () {

        Route::get('', "ApiController@getAll");
        Route::post('', "ApiController@create");

        Route::group(['prefix' => '{id}'], function () {
            Route::get('', "ApiController@get");
            Route::delete('', "ApiController@delete");
            Route::put('', "ApiController@update");
            Route::put('/active', "ApiController@activate");
            Route::delete('/active', "ApiController@deactivate");
        });
    });

    // scopes
    Route::group(['prefix' => 'scopes', 'middleware' => ['oauth2.currentuser.serveradmin.json']], function () {

        Route::get('/', "ApiScopeController@getAll");
        Route::post('/', "ApiScopeController@create");

        Route::group(['prefix' => '{id}'], function () {
            Route::get('', "ApiScopeController@get");
            Route::delete('', "ApiScopeController@delete");
            Route::put('', "ApiScopeController@update");
            Route::put('/active', "ApiScopeController@activate");
            Route::delete('/active', "ApiScopeController@deactivate");
        });
    });

    // endpoints
    Route::group(['prefix' => 'endpoints', 'middleware' => ['oauth2.currentuser.serveradmin.json']], function () {

        Route::get('', "ApiEndpointController@getAll");
        Route::post('', "ApiEndpointController@create");

        Route::group(['prefix' => '{id}'], function () {
            Route::get('', "ApiEndpointController@get");
            Route::delete('', "ApiEndpointController@delete");
            Route::put('', "ApiEndpointController@update");
            Route::put('/active', "ApiEndpointController@activate");
            Route::delete('/active', "ApiEndpointController@deactivate");
            Route::group(['prefix' => 'scope'], function () {
                Route::group(['prefix' => '{scope_id}'], function () {
                    Route::put('', "ApiEndpointController@addRequiredScope");
                    Route::delete('', "ApiEndpointController@removeRequiredScope");
                });
            });
        });
    });

    // private keys
    Route::group(array('prefix' => 'private-keys', 'middleware' => ['oauth2.currentuser.serveradmin.json']), function () {
        Route::get('', "ServerPrivateKeyApiController@getAll");
        Route::post('', "ServerPrivateKeyApiController@create");

        Route::group(['prefix' => '{id}'], function () {
            Route::delete('', "ServerPrivateKeyApiController@delete");
            Route::put('', "ServerPrivateKeyApiController@update");
        });
    });
});