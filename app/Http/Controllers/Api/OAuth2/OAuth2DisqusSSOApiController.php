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
use App\Services\Auth\IDisqusSSOService;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\IResourceServerContext;
use Utils\Services\ILogService;
use App\libs\OAuth2\IUserScopes;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
/**
 * Class OAuth2DisqusSSOApiController
 * @package App\Http\Controllers\Api\OAuth2
 */
final class OAuth2DisqusSSOApiController extends OAuth2ProtectedController
{

    /**
     * @var IDisqusSSOService
     */
    private $service;

    public function __construct
    (
        IDisqusSSOService $service,
        IResourceServerContext $resource_server_context,
        ILogService $log_service
    )
    {
        parent::__construct($resource_server_context, $log_service);
        $this->service = $service;
    }

    #[OA\Get(
        path: '/api/v1/sso/disqus/{forum_slug}/profile',
        operationId: 'getDisqusUserProfile',
        summary: 'Get Disqus user profile for a forum',
        security: [['OAuth2DisqusSSOSecurity' => [IUserScopes::SSO]]],
        tags: ['Disqus SSO'],
        parameters: [
            new OA\Parameter(
                name: 'forum_slug',
                description: 'Forum slug',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: HttpResponse::HTTP_OK,
                description: 'OK',
                content: new OA\JsonContent(ref: '#/components/schemas/DisqusUserProfileSerialized')
            ),
            new OA\Response(
                response: HttpResponse::HTTP_NOT_FOUND,
                description: 'Not Found'
            ),
            new OA\Response(
                response: HttpResponse::HTTP_PRECONDITION_FAILED,
                description: 'Validation Error'
            ),
            new OA\Response(
                response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Server Error'
            ),
        ]
    )]
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