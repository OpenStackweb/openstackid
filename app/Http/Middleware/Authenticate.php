<?php namespace App\Http\Middleware;
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
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
/**
 * Class Authenticate
 * @package App\Http\Middleware
 */
class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
       if (Auth::guard($guard)->guest()) {
            Session::put('backurl', URL::full());
            Session::save();
            return Redirect::action('UserController@getLogin');
        }

        // log debug info about the user and his request
        $url = $request->getRequestUri();
        $method = $request->getMethod();
        $current_user = Auth::user();
        Log::debug
        (
            sprintf
            (
                "Authenticate::handle method %s url %s current user %s (%s) groups %s",
                $method,
                $url,
                $current_user->getEmail(),
                $current_user->getId(),
                $current_user->getGroupsNice()
            )
        );
        $redirect = Session::get('backurl');
        if (!empty($redirect)) {
            Session::forget('backurl');
            Session::save();
            Log::debug
            (
                sprintf
                (
                    "Authenticate::handle method %s url %s current user %s (%s) groups %s redirecting to %s",
                    $method,
                    $url,
                    $current_user->getEmail(),
                    $current_user->getId(),
                    $current_user->getGroupsNice(),
                    $redirect
                )
            );
            return Redirect::to($redirect);
        }

        return $next($request);
    }
}
