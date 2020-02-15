<?php namespace App\Http\Middleware;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Http\Utils\CookieSameSitePolicy;
use Closure;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Class CookiesSameSiteNoneKnownIncompatibleClients
 * @package App\Http\Middleware
 */
class CookiesSameSiteNoneKnownIncompatibleClients
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // check if user agent is compatible or not with SameSite=None
        // https://www.chromium.org/updates/same-site/incompatible-clients
        foreach ($response->headers->getCookies() as $cookie) {
            $sameSite =  $cookie->getSameSite();
            if($sameSite == Cookie::SAMESITE_NONE){
                // check if we could use it or not
                if(CookieSameSitePolicy::isSameSiteNoneIncompatible()){
                    // replace the cookie with a compatible version ( unset sameSite value)

                    // make a clone
                    $compatibleCookie = Cookie::create
                    (
                        $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpiresTime(),
                        $cookie->getPath(),
                        $cookie->getDomain(),
                        $cookie->isSecure(),
                        $cookie->isHttpOnly(),
                        $cookie->isRaw(),
                        null
                    );
                    // and overwrite it
                    $response->headers->setCookie($compatibleCookie);
                }
            }
        }
        return $response;
    }
}