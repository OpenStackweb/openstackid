<?php namespace App\Http\Controllers\Api\OAuth2;
/**
 * Copyright 2025 OpenStack Foundation
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
use App\libs\Auth\Repositories\IGroupRepository;
use App\ModelSerializers\SerializerRegistry;
use OAuth2\IResourceServerContext;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Utils\Services\ILogService;

/**
 * Class OAuth2GroupApiController
 * @package App\Http\Controllers\Api\OAuth2
 */
final class OAuth2GroupApiController extends OAuth2ProtectedController
{
    use GetAllTrait;

    #[OA\Get(
        path: '/api/v1/groups',
        operationId: 'getGroups',
        summary: 'Get all groups',
        description: 'Retrieves a paginated list of groups with optional filtering and ordering.',
        tags: ['Groups'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number for pagination',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1, example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of items per page',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 5, maximum: 100, default: 5, example: 10)
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                description: 'Filter criteria. Supported filters: slug== (exact match). Example: filter=slug==administrators',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'slug==administrators')
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                description: 'Ordering criteria. Supported fields: id, name, slug. Use + for ascending, - for descending. Example: +name or -id',
                required: false,
                schema: new OA\Schema(type: 'string', example: '+name')
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful response with paginated groups',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedGroupResponseSchema')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server error')
        ]
    )]

    /**
     * OAuth2UserApiController constructor.
     * @param IGroupRepository $repository
     * @param IResourceServerContext $resource_server_context
     * @param ILogService $log_service
     */
    public function __construct
    (
        IGroupRepository $repository,
        IResourceServerContext $resource_server_context,
        ILogService $log_service,
    )
    {
        parent::__construct($resource_server_context, $log_service);
        $this->repository = $repository;
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
            'slug' => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'slug' => 'sometimes|required|string',
        ];
    }

    public function getOrderRules(): array
    {
        return [
            'id',
            'name',
            'slug'
        ];
    }
}