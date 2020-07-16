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
use App\Models\Repositories\IStreamChatSSOProfileRepository;
use App\Models\SSO\StreamChat\StreamChatUserProfile;
use App\Services\AbstractService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\IResourceServerContext;
use Utils\Db\ITransactionService;
use GetStream\StreamChat\Client as StreamChatClient;
/**
 * Class StreamChatSSOService
 * @package App\Services\Auth
 */
final class StreamChatSSOService
    extends AbstractService
    implements IStreamChatSSOService
{

    /**
     * @var IStreamChatSSOProfileRepository
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

    public function __construct
    (
        IStreamChatSSOProfileRepository $repository,
        IResourceServerContext $resource_server_context,
        IUserRepository $user_repository,
        ITransactionService $tx_service
    )
    {
        $this->repository = $repository;
        $this->user_repository = $user_repository;
        $this->resource_server_context = $resource_server_context;
        parent::__construct($tx_service);
    }

    /**
     * @inheritDoc
     */
    public function getUserProfile(string $forum_slug): ?StreamChatUserProfile
    {
        return $this->tx_service->transaction(function() use($forum_slug){

            Log::debug("StreamChatService::getUserProfile");
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

            $client = new StreamChatClient($sso_profile->getApiKey(), $sso_profile->getApiSecret());
            $token = $client->createToken($current_user->getIdentifier());

            $chat_user = $client->updateUser([
                'id' => strval($current_user->getId()),
                'role' => $current_user->isSuperAdmin()? 'admin' : 'user',
                'name' => $current_user->getFullName(),
                'image' => $current_user->getPic(),
            ]);

            return new StreamChatUserProfile
            (
                strval($current_user->getId()),
                $current_user->getFullName(),
                $current_user->getPic(),
                $token,
                $sso_profile->getApiKey()
            );
        });
    }
}