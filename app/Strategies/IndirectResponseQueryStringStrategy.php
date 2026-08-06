<?php namespace Strategies;
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
use Utils\IHttpResponseStrategy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
/**
 * Class IndirectResponseQueryStringStrategy
 * Redirect and http response using a 302 adding params on query string
 * @package Strategies
 */
class IndirectResponseQueryStringStrategy implements IHttpResponseStrategy
{

    /**
     * @param $response
     * @return mixed
     */
    public function handle($response)
    {
        $query_string = $response->getContent();
        $return_to    = $response->getReturnTo();

        if (is_null($return_to) || empty($return_to)) {
            return Response::view('errors.404', [], 404);
        }
        $return_to = (strpos($return_to, "?") == false) ? $return_to . "?" . $query_string : $return_to . "&" . $query_string;

        // RFC 8252 SS7.1 authority-less URIs (com.example.app:/cb?code=...) fail Laravel's
        // UrlGenerator::isValidUrl(), so Redirect::to() would treat the already-validated redirect
        // target as a RELATIVE path and prefix the site URL, corrupting the redirect. For an absolute
        // URI (leading scheme) Laravel does not recognize, emit the Location verbatim - Symfony still
        // rejects CR/LF in the header value, so no header-injection surface is opened.
        $redirect = (!URL::isValidUrl($return_to) && preg_match('~^[A-Za-z][A-Za-z0-9+.\-]*:~', $return_to) === 1)
            ? new RedirectResponse($return_to)
            : Redirect::to($return_to);

        return $redirect
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma','no-cache');
    }
}