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
use App\Models\Repositories\IDisqusSSOProfileRepository;
use App\Models\SSO\Disqus\DisqusUserProfile;
use App\Services\AbstractService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\utils\IEntity;
use OAuth2\IResourceServerContext;
use Utils\Db\ITransactionService;
/**
 * Class DisqusSSOService
 * @package App\Services\Auth
 */
final class DisqusSSOService extends AbstractService implements IDisqusSSOService
{

    /**
     * @var IDisqusSSOProfileRepository
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
     * DisqusSSOService constructor.
     * @param IResourceServerContext $resource_server_context
     * @param IUserRepository $user_repository
     * @param IDisqusSSOProfileRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (

        IResourceServerContext $resource_server_context,
        IUserRepository $user_repository,
        IDisqusSSOProfileRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->user_repository = $user_repository;
        $this->resource_server_context = $resource_server_context;
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
     * @return DisqusUserProfile|null
     * @throws \Exception
     */
    public function getUserProfile(string $forum_slug): ?DisqusUserProfile
    {
       return $this->tx_service->transaction(function() use($forum_slug){

           Log::debug("DisqusSSOService::getUserProfile");
           $current_user_id = $this->resource_server_context->getCurrentUserId();

           Log::debug(sprintf("DisqusSSOService::getUserProfile current_user_id %s", $current_user_id));
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

           return new DisqusUserProfile($sso_profile, $current_user);
       });
    }
}