<?php namespace App\Http\Controllers\Api\OAuth2;
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
use App\Services\Auth\IStreamChatSSOService;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\IResourceServerContext;
use Utils\Services\ILogService;
/**
 * Class OAuth2StreamChatSSOApiController
 * @package App\Http\Controllers\Api\OAuth2
 */
class OAuth2StreamChatSSOApiController extends OAuth2ProtectedController
{
    /**
     * @var IStreamChatSSOService
     */
    private $service;


    public function __construct
    (
        IStreamChatSSOService $service,
        IResourceServerContext $resource_server_context,
        ILogService $log_service
    )
    {
        parent::__construct($resource_server_context, $log_service);
        $this->service = $service;
    }

    /**
     * @param string $forum_slug
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getUserProfile(string $forum_slug){
        try{
            $profile = $this->service->getUserProfile($forum_slug);
            return $this->ok($profile->serialize());
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412([$ex->getMessage()]);
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404(['message'=> $ex->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}