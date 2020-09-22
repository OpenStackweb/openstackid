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

use App\libs\Auth\Models\IGroupSlugs;
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

            // @see https://github.com/nparsons08/stream-chat-boilerplate-api/blob/master/src/controllers/v1/token/token.action.js
            // @see https://getstream.io/chat/docs/tokens_and_authentication/?language=php
            $client = new StreamChatClient($sso_profile->getApiKey(), $sso_profile->getApiSecret());
            $token = $client->createToken(strval($current_user->getId()));

            /**
             * Available roles
             * https://getstream.io/chat/docs/channel_user_role/?language=js
             *  user
             *  guest
             *  admin
             */
            $role = 'user';
            $localRole = 'user';
            $isAdmin = $current_user->isSuperAdmin() || $current_user->isAdmin();
            $isChatQA = $current_user->belongToGroup(IGroupSlugs::ChatQAGroup);
            $isChatHelp = $current_user->belongToGroup(IGroupSlugs::ChatHelpGroup);

            if($isChatQA && $isChatHelp){
                $localRole = 'help-qa-user';
            }
            else if($isChatQA){
                $localRole = 'qa-user';
            }
            else if($isChatHelp){
                $localRole = 'help-user';
            }
            else if($isAdmin){
                $role = 'admin';
                $localRole = 'admin';
            }
            // register user on stream api
            $client->updateUser([
                'id' => strval($current_user->getId()),
                'role' => $role,
                'name' => $current_user->getFullName(),
                'image' => $current_user->getPic(),
                'local_role' => $localRole
            ]);

            return new StreamChatUserProfile
            (
                strval($current_user->getId()),
                $current_user->getFullName(),
                $current_user->getPic(),
                $token,
                $sso_profile->getApiKey(),
                $localRole
            );
        });
    }
}