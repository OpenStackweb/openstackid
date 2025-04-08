<?php namespace App\Listeners;
/**
 * Copyright 2024 OpenStack Foundation
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

use App\Jobs\RevokeUserGrantsOnExplicitLogout;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

/**
 * Class OnUserLogout
 * @package App\Listeners
 */
class OnUserLogout
{

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Logout $event)
    {
        $user = $event->user;
        Log::debug(sprintf("OnUserLogout::handle user %s (%s)", $user->getEmail(), $user->getId()));
        RevokeUserGrantsOnExplicitLogout::dispatch($user);
    }
}
