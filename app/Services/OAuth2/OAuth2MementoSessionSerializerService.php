<?php namespace Services\OAuth2;
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

use Illuminate\Support\Facades\Log;
use OAuth2\Requests\OAuth2RequestMemento;
use OAuth2\Services\IMementoOAuth2SerializerService;
use Illuminate\Support\Facades\Session;
/**
 * Class OAuth2MementoSessionSerializerService
 * @package Services\OAuth2
 */
final class OAuth2MementoSessionSerializerService implements IMementoOAuth2SerializerService
{

    const StateKey = 'oauth2.request.state';
    /**
     * @param OAuth2RequestMemento $memento
     * @return void
     */
    public function serialize(OAuth2RequestMemento $memento):void
    {
        $state = json_encode($memento->getState());
        Log::debug(sprintf("OAuth2MementoSessionSerializerService::serialize state %s", $state));
        $state = base64_encode($state);
        Session::put(self::StateKey, $state);
        Session::save();
    }

    /**
     * @return OAuth2RequestMemento
     */
    public function load():?OAuth2RequestMemento
    {
        $state = Session::get(self::StateKey, null);

        if(is_null($state)){
            Log::warning(sprintf("OAuth2MementoSessionSerializerService::load state is null"));
            return null;
        }

        $state = base64_decode($state);
        Log::debug(sprintf("OAuth2MementoSessionSerializerService::load state %s", $state));
        $state = json_decode( $state, true);

        return OAuth2RequestMemento::buildFromState($state);
    }

    /**
     * @return void
     */
    public function forget():void
    {
        Log::debug(sprintf("OAuth2MementoSessionSerializerService::forget"));
        Session::remove(self::StateKey);
        Session::save();
    }

    /**
     * @return bool
     */
    public function exists():bool
    {
        $res = Session::has(self::StateKey);

        Log::debug(sprintf("OAuth2MementoSessionSerializerService::exists %b", $res));

        return $res;
    }
}