<?php namespace App\Services\Auth;
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
use App\Models\Repositories\IRocketChatSSOProfileRepository;
use App\Models\SSO\RocketChatUserProfile;
use App\Services\AbstractService;
use App\Services\Apis\IRocketChatAPI;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\utils\IEntity;
use OAuth2\IResourceServerContext;
use Utils\Db\ITransactionService;
/**
 * Class RocketChatSSOService
 * @package App\Services\Auth
 */
final class RocketChatSSOService extends AbstractService implements IRocketChatSSOService
{

    /**
     * @var IRocketChatSSOProfileRepository
     */
    private $repository;

    /**
     * @var IResourceServerContext
     */
    private $resource_server_context;

    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * @var IRocketChatAPI
     */
    private $rocket_chat_api;

    /**
     * RocketChatSSOService constructor.
     * @param IResourceServerContext $resource_server_context
     * @param IUserRepository $user_repository
     * @param IRocketChatSSOProfileRepository $repository
     * @param IRocketChatAPI $rocket_chat_api
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IResourceServerContext $resource_server_context,
        IUserRepository $user_repository,
        IRocketChatSSOProfileRepository $repository,
        IRocketChatAPI $rocket_chat_api,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->user_repository = $user_repository;
        $this->resource_server_context = $resource_server_context;
        $this->rocket_chat_api = $rocket_chat_api;
    }

    /**
     * @inheritDoc
     */
    public function create(array $payload): IEntity
    {
        // TODO: Implement create() method.
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $payload): IEntity
    {
        // TODO: Implement update() method.
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @param string $forum_slug
     * @return RocketChatUserProfile|null
     * @throws \Exception
     */
    public function getUserProfile(string $forum_slug): ?RocketChatUserProfile
    {
        return $this->tx_service->transaction(function() use($forum_slug){

            Log::debug("RocketChatSSOService::getUserProfile");
            $current_user_id = $this->resource_server_context->getCurrentUserId();
            $access_token    = $this->resource_server_context->getCurrentAccessToken();
            if(empty($access_token)){
                throw new ValidationException("Access Token is empty.");
            }
            Log::debug(sprintf("RocketChatSSOService::getUserProfile current_user_id %s", $current_user_id));
            if (is_null($current_user_id)) {
                throw new ValidationException('me is no set!.');
            }

            $current_user = $this->user_repository->getById($current_user_id);
            if(is_null($current_user)) throw new EntityNotFoundException();

            if(!$current_user instanceof User) throw new EntityNotFoundException();
            $sso_profile = $this->repository->getByForumSlug($forum_slug);
            if(is_null($sso_profile)){
                throw new EntityNotFoundException("Forum not found");
            }

            return new RocketChatUserProfile
            (
                $this->rocket_chat_api->setBaseUrl($sso_profile->getBaseUrl())->login($sso_profile->getServiceName())
            );
        });
    }
}