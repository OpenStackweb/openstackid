<?php namespace App\Http\Controllers\Api\OAuth2;
/**
 * Copyright 2019 OpenStack Foundation
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

use App\Http\Controllers\GetAllTrait;
use App\libs\Auth\Repositories\IUserRegistrationRequestRepository;
use App\libs\OAuth2\IUserScopes;
use App\ModelSerializers\SerializerRegistry;
use App\Services\Auth\IUserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\IResourceServerContext;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Utils\Services\ILogService;
/**
 * Class OAuth2UserRegistrationRequestApiController
 * @package App\Http\Controllers\Api\OAuth2
 */
final class OAuth2UserRegistrationRequestApiController extends OAuth2ProtectedController
{

    use GetAllTrait;

    /**
     * @var IUserService
     */
    private $user_service;

    /**
     * @param IUserRegistrationRequestRepository $repository
     * @param IUserService $user_service
     * @param IResourceServerContext $resource_server_context
     * @param ILogService $log_service
     */
    #[OA\Get(
        path: '/api/v1/user-registration-requests',
        operationId: 'getUserRegistrationRequests',
        summary: 'Get all user registration requests',
        security: [['OAuth2UserRegistrationRequestApi' => [IUserScopes::Registration]]],
        tags: ['User Registration Requests'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'expand',
                description: 'Expand relations',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter',
                description: 'Filter criteria (first_name, last_name, email, is_redeemed)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'order',
                description: 'Order criteria (id)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: HttpResponse::HTTP_OK,
                description: 'OK',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedUserRegistrationRequestResponse')
            ),
            new OA\Response(
                response: HttpResponse::HTTP_PRECONDITION_FAILED,
                description: 'Precondition Failed'
            ),
            new OA\Response(
                response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Server Error'
            ),
        ]
    )]
    public function __construct
    (
        IUserRegistrationRequestRepository  $repository,
        IUserService $user_service,
        IResourceServerContext $resource_server_context,
        ILogService $log_service
    )
    {
        parent::__construct($resource_server_context, $log_service);
        $this->repository = $repository;
        $this->user_service = $user_service;
    }

    protected function getAllSerializerType(): string
    {
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'first_name' => ['=@', '=='],
            'last_name' => ['=@', '=='],
            'email' => ['=@', '=='],
            'is_redeemed' => ['==']
        ];
    }

    public function getOrderRules(): array
    {
        return [
            'id',
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'first_name' => 'sometimes|required|string',
            'last_name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email',
            'is_redeemed' => 'sometimes|required|boolean'
        ];
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: '/api/v1/user-registration-requests',
        operationId: 'createUserRegistrationRequest',
        summary: 'Create a user registration request',
        security: [['OAuth2UserRegistrationRequestApi' => [IUserScopes::Registration]]],
        tags: ['User Registration Requests'],
        requestBody: new OA\RequestBody(
            description: 'User registration request data',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateUserRegistrationRequestRequest')
        ),
        responses: [
            new OA\Response(
                response: HttpResponse::HTTP_CREATED,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/UserRegistrationRequest')
            ),
            new OA\Response(
                response: HttpResponse::HTTP_BAD_REQUEST,
                description: 'Bad Request'
            ),
            new OA\Response(
                response: HttpResponse::HTTP_PRECONDITION_FAILED,
                description: 'Precondition Failed'
            ),
            new OA\Response(
                response: HttpResponse::HTTP_NOT_FOUND,
                description: 'Not Found'
            ),
            new OA\Response(
                response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Server Error'
            ),
        ]
    )]
    public function register(){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'first_name'               => 'nullable|sometimes|string|max:100',
                'last_name'                => 'nullable|sometimes|string|max:100',
                'company'                  => 'nullable|sometimes|string|max:100',
                'email'                    => 'required|string|email|max:255',
                'country'                  => 'sometimes|required|string|country_iso_alpha2_code',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $registration_request = $this->user_service->createRegistrationRequest
            (
                $this->resource_server_context->getCurrentClientId(),
                $payload
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($registration_request)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/user-registration-requests/{id}',
        operationId: 'updateUserRegistrationRequest',
        summary: 'Update a user registration request',
        security: [['OAuth2UserRegistrationRequestApi' => [IUserScopes::Registration]]],
        tags: ['User Registration Requests'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Registration request ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            description: 'User registration request data to update',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateUserRegistrationRequestRequest')
        ),
        responses: [
            new OA\Response(
                response: HttpResponse::HTTP_OK,
                description: 'OK',
                content: new OA\JsonContent(ref: '#/components/schemas/UserRegistrationRequest')
            ),
            new OA\Response(
                response: HttpResponse::HTTP_BAD_REQUEST,
                description: 'Bad Request'
            ),
            new OA\Response(
                response: HttpResponse::HTTP_PRECONDITION_FAILED,
                description: 'Precondition Failed'
            ),
            new OA\Response(
                response: HttpResponse::HTTP_NOT_FOUND,
                description: 'Not Found'
            ),
            new OA\Response(
                response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Server Error'
            ),
        ]
    )]
    public function update($id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'first_name'               => 'nullable|sometimes|string|max:100',
                'last_name'                => 'nullable|sometimes|string|max:100',
                'company'                  => 'nullable|sometimes|string|max:100',
                'country'                  => 'nullable|sometimes|required|string|country_iso_alpha2_code',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $registration_request = $this->user_service->updateRegistrationRequest
            (
                intval($id),
                $payload
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($registration_request)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}