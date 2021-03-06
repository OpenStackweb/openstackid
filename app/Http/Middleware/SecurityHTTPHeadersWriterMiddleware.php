<?php namespace App\Http\Middleware;
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

use Closure;
use libs\utils\RequestUtils;

/**
* Class SecurityHTTPHeadersWriterMiddleware
* https://www.owasp.org/index.php/List_of_useful_HTTP_headers
*
* @package App\Http\Middleware
*/
class SecurityHTTPHeadersWriterMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 * @return \Illuminate\Http\Response
	 */
	public function handle($request, Closure $next)
	{

		$response = $next($request);
       // https://www.owasp.org/index.php/List_of_useful_HTTP_headers
		$response->headers->set('X-Content-Type-Options','nosniff');
		// https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection
		$response->headers->set('X-XSS-Protection','1; mode=block');
		// cache
        /**
         * Whenever possible ensure the cache-control HTTP header is set with no-cache, no-store, must-revalidate;
         * and that the pragma HTTP header is set with no-cache.
         */
		$response->headers->set('Pragma','no-cache');
		$response->headers->set('Expires','0');
		$response->headers->set('Cache-Control','no-cache, no-store, must-revalidate, private');
		return $response;
	}
}