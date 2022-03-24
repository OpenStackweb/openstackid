<?php namespace Services\OpenId;
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

use App\Http\Utils\SessionConstants;
use OpenId\Requests\OpenIdMessageMemento;
use OpenId\Services\IMementoOpenIdSerializerService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
/**
 * Class OpenIdMementoSessionSerializerService
 * @package Services\OpenId
 */
class OpenIdMementoSessionSerializerService implements IMementoOpenIdSerializerService
{

    /**
     * @param OpenIdMessageMemento $memento
     * @return void
     */
    public function serialize(OpenIdMessageMemento $memento)
    {
        $state = base64_encode(json_encode($memento->getState()));
        Log::debug(sprintf("OpenIdMementoSessionSerializerService::serialize %s", $state));
        Session::put(SessionConstants::OpenIdRequestState, $state);
        Session::save();
    }

    /**
     * @return OpenIdMessageMemento
     */
    public function load()
    {
        Log::debug(sprintf("OpenIdMementoSessionSerializerService::load"));

        $state = Session::get(SessionConstants::OpenIdRequestState, null);

        if(is_null($state)) {
            Log::warning(sprintf("OpenIdMementoSessionSerializerService::load openid.request.state is null"));
            return null;
        }

        $state = json_decode( base64_decode($state), true);

        return OpenIdMessageMemento::buildFromState($state);
    }

    /**
     * @return void
     */
    public function forget()
    {
        Log::debug(sprintf("OpenIdMementoSessionSerializerService::forget"));
        Session::remove(SessionConstants::OpenIdRequestState);
        Session::save();
    }

    /**
     * @return bool
     */
    public function exists()
    {
        Log::debug(sprintf("OpenIdMementoSessionSerializerService::exists"));
        return Session::has(SessionConstants::OpenIdRequestState);
    }
}