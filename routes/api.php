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
| OAuth2 Protected API
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'users'], function () {
    Route::get('', 'OAuth2UserApiController@getAll');
    Route::get('/{id}', 'OAuth2UserApiController@get');

    Route::group(['prefix' => 'me'], function () {
        Route::get('', 'OAuth2UserApiController@me');
        Route::match(['options', 'put'], '', 'OAuth2UserApiController@UpdateMe');
        Route::group(['prefix' => 'pic'], function () {
            Route::match(['options', 'put'], '', 'OAuth2UserApiController@UpdateMyPic');
        });
    });

    Route::get('/info', 'OAuth2UserApiController@userInfo');
    Route::post('/info', 'OAuth2UserApiController@userInfo');
});

Route::group(['prefix' => 'user-registration-requests'], function () {
    Route::match(['options', 'post'], '', 'OAuth2UserRegistrationRequestApiController@register');
});

// 3rd Party SSO integrations

Route::group(['prefix' => 'sso'], function () {

    Route::group(['prefix' => 'disqus'], function () {
        Route::group(['prefix' => '{forum_slug}'], function () {
            Route::get('profile', 'OAuth2DisqusSSOApiController@getUserProfile');
        });
    });

    Route::group(['prefix' => 'rocket-chat'], function () {
        Route::group(['prefix' => '{forum_slug}'], function () {
            Route::get('profile', 'OAuth2RocketChatSSOApiController@getUserProfile');
        });
    });

    Route::group(['prefix' => 'stream-chat'], function () {
        Route::group(['prefix' => '{forum_slug}'], function () {
            Route::get('profile', 'OAuth2StreamChatSSOApiController@getUserProfile');
        });
    });
});