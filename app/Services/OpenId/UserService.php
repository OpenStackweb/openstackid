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

use App\Events\UserEmailUpdated;
use App\Events\UserPasswordResetSuccessful;
use App\Jobs\AddUserAction;
use App\Jobs\PublishUserDeleted;
use App\Jobs\PublishUserUpdated;
use App\libs\Auth\Factories\UserFactory;
use App\libs\Auth\Repositories\IGroupRepository;
use App\libs\Utils\FileSystem\FileNameSanitizer;
use App\Mail\MonitoredSecurityGroupNotificationEmail;
use App\Services\AbstractService;
use App\Services\Auth\IUserIdentifierGeneratorService;
use Auth\Group;
use Auth\IUserNameGeneratorService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\utils\IEntity;
use OAuth2\IResourceServerContext;
use OAuth2\Models\IClient;
use OAuth2\Repositories\IClientRepository;
use OpenId\Services\IUserService;
use Utils\Db\ITransactionService;
use Utils\IPHelper;
use Utils\Services\ILogService;
use Utils\Services\IServerConfigurationService;

/**
 * Class UserService
 * @package Services\OpenId
 */
final class UserService extends AbstractService implements IUserService
{

    /**
     * @var IUserRepository
     */
    private $repository;
    /**
     * @var ILogService
     */
    private $log_service;

    /**
     * @var IUserNameGeneratorService
     */
    private $user_name_generator;

    /**
     * @var IServerConfigurationService
     */
    private $configuration_service;

    /**
     * @var IGroupRepository
     */
    private $group_repository;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var IResourceServerContext
     */
    private $server_ctx;

    /**
     * @var IUserIdentifierGeneratorService
     */
    private $identifier_service;

    /**
     * UserService constructor.
     * @param IUserRepository $repository
     * @param IGroupRepository $group_repository
     * @param IUserNameGeneratorService $user_name_generator
     * @param ITransactionService $tx_service
     * @param IServerConfigurationService $configuration_service
     * @param ILogService $log_service
     * @param IResourceServerContext $server_ctx
     * @param IUserIdentifierGeneratorService $identifier_service
     */
    public function __construct
    (
        IUserRepository $repository,
        IGroupRepository $group_repository,
        IUserNameGeneratorService $user_name_generator,
        ITransactionService $tx_service,
        IServerConfigurationService $configuration_service,
        ILogService $log_service,
        IResourceServerContext $server_ctx,
        IUserIdentifierGeneratorService $identifier_service,
        IClientRepository $client_repository
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->group_repository = $group_repository;
        $this->user_name_generator = $user_name_generator;
        $this->configuration_service = $configuration_service;
        $this->log_service = $log_service;
        $this->server_ctx = $server_ctx;
        $this->identifier_service = $identifier_service;
        $this->client_repository = $client_repository;
    }

    private function addUserCRUDAction(User $user, $payload, string $action_type = "CREATE") {
        $payload_json = json_encode($payload);
        $current_user_id = $this->server_ctx->getCurrentUserId();

        if (!is_null($current_user_id)) {
            $action = "{$action_type} USER BY USER {$this->server_ctx->getCurrentUserEmail()} ({$current_user_id}): {$payload_json}";
            AddUserAction::dispatch($user->getId(), IPHelper::getUserIp(), $action);
            return;
        }

        //check if it's a service app
        $app_type = $this->server_ctx->getApplicationType();
        if (!empty($app_type) && $app_type == IClient::ApplicationType_Service) {
            $action = "{$action_type} USER BY SERVICE {$this->server_ctx->getCurrentClientId()}: {$payload_json}";
            AddUserAction::dispatch($user->getId(), IPHelper::getUserIp(), $action);
            return;
        }

        $action = "{$action_type} USER: {$payload_json}";
        AddUserAction::dispatch($user->getId(), IPHelper::getUserIp(), $action);
        return;
    }

    /**
     * @param int $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function updateLastLoginDate(int $user_id): User
    {
        return $this->tx_service->transaction(function () use ($user_id) {
            $user = $this->repository->getById($user_id);
            if (is_null($user) || !$user instanceof User) throw new EntityNotFoundException();
            $user->updateLastLoginDate();
            return $user;
        });
    }

    /**
     * @param int $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function updateFailedLoginAttempts(int $user_id): User
    {
        return $this->tx_service->transaction(function () use ($user_id) {
            $user = $this->repository->getById($user_id);
            if (!$user instanceof User) throw new EntityNotFoundException();
            $user->updateLoginFailedAttempt();
            return $user;
        });
    }

    /**
     * @param int $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function lockUser(int $user_id): User
    {
        return $this->tx_service->transaction(function () use ($user_id) {
            $user = $this->repository->getById($user_id);
            if (!$user instanceof User) throw new EntityNotFoundException();
            return $user->lock();
        });
    }

    /**
     * @param int $user_id
     * @return void
     * @throws EntityNotFoundException
     */
    public function unlockUser(int $user_id): User
    {
        return $this->tx_service->transaction(function () use ($user_id) {
            $user = $this->repository->getById($user_id);
            if (!$user instanceof User) throw new EntityNotFoundException();
            return $user->unlock();
        });
    }

    /**
     * @param int $user_id
     * @param bool $show_pic
     * @param bool $show_full_name
     * @param bool $show_email
     * @param string $identifier
     * @return User
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function saveProfileInfo($user_id, $show_pic, $show_full_name, $show_email, $identifier): User
    {

        return $this->tx_service->transaction(function () use ($user_id, $show_pic, $show_full_name, $show_email, $identifier) {
            $user = $this->repository->getById($user_id);
            if (is_null($user) || !$user instanceof User) throw new EntityNotFoundException();

            $former_user = $this->repository->getByIdentifier($identifier);

            if (!is_null($former_user) && $former_user->getId() != $user_id) {
                throw new ValidationException("there is already another user with that openid identifier");
            }

            $user->setPublicProfileShowPhoto($show_pic);
            $user->setPublicProfileShowFullname($show_full_name);
            $user->setPublicProfileShowEmail($show_email);
            $user->setIdentifier($identifier);

            return $user;
        });
    }

    /**
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function create(array $payload): IEntity
    {
        $user = $this->tx_service->transaction(function () use ($payload) {
            if (isset($payload["email"])) {
                $former_user = $this->repository->getByEmailOrName(trim($payload["email"]));
                if (!is_null($former_user))
                    throw new ValidationException(sprintf("email %s already belongs to another user", $payload["email"]));
            }

            if (isset($payload["identifier"]) && !empty($payload["identifier"])) {
                $former_user = $this->repository->getByIdentifier(trim($payload["identifier"]));
                if (!is_null($former_user))
                    throw new ValidationException(sprintf("identifier %s already belongs to another user", $payload["identifier"]));
            }

            $user = UserFactory::build($payload);

            if (isset($payload['groups'])) {
                foreach ($payload['groups'] as $group_id) {
                    $group = $this->group_repository->getById($group_id);
                    if (is_null($group))
                        throw new EntityNotFoundException("group not found");
                    $user->addToGroup($group);
                }
            }
            $this->identifier_service->generateIdentifier($user);
            $this->repository->add($user);

            return $user;
        });

        $this->addUserCRUDAction($user, $payload);

        return $user;
    }

    /**
     * @param int $id
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function update(int $id, array $payload): IEntity
    {
        $user = $this->tx_service->transaction(function () use ($id, $payload) {

            $user = $this->repository->getById($id);

            if (!$user instanceof User)
                throw new EntityNotFoundException("User not found.");

            $former_email = $user->getEmail();
            $former_password = $user->getPassword();
            $current_user = !empty($this->server_ctx->getCurrentUserId()) ? $this->repository->getById($this->server_ctx->getCurrentUserId()) : Auth::user();
            $should_ask_4_cur_password_validation = !(!is_null($current_user) &&
                    $user->getId() != $current_user->getId() && // is not the same user as current user
                    ($current_user->isAdmin() || $current_user->isSuperAdmin()) && // current user should be admin
                    (!$user->isAdmin() && !$user->isSuperAdmin())) // user to update is not admin
                && $user->hasPasswordSet();

            if ($should_ask_4_cur_password_validation) {
                if (isset($payload["password"]) && !empty($payload["password"])) {
                    // changing password
                    if (!isset($payload['current_password']))
                        throw new ValidationException(sprintf("current_password is needed to update user password."));

                    if (!$user->checkPassword(trim($payload['current_password']))) {
                        throw new ValidationException(sprintf("current_password is not correct."));
                    }
                }
            }

            if (isset($payload["email"]) && !empty($payload["email"])) {
                $former_user = $this->repository->getByEmailOrName(trim($payload["email"]));
                if (!is_null($former_user) && $former_user->getId() != $id)
                    throw new ValidationException(sprintf("email %s already belongs to another user", $payload["email"]));
            }

            if (isset($payload["identifier"]) && !empty($payload["identifier"])) {
                $former_user = $this->repository->getByIdentifier(trim($payload["identifier"]));
                if (!is_null($former_user) && $former_user->getId() != $id)
                    throw new ValidationException(sprintf("identifier %s already belongs to another user", $payload["identifier"]));
            }

            $user = UserFactory::populate($user, $payload);

            if (isset($payload['groups'])) {
                // update groups
                // Get current groups
                $current_groups = $user->getGroups();

                $new_groups = new ArrayCollection();

                foreach ($payload['groups'] as $group_id) {
                    $group = $this->group_repository->getById($group_id);
                    if (!$group instanceof Group) {
                        throw new EntityNotFoundException("Group not found.");
                    }
                    $new_groups->add($group);
                }

                // Remove groups not in the new list
                foreach ($current_groups as $group) {
                    if (!$new_groups->contains($group)) {
                        $user->removeFromGroup($group);
                    }
                }

                // Add new groups not already in current
                foreach ($new_groups as $group) {
                    if (!$current_groups->contains($group)) {
                        $user->addToGroup($group);
                    }
                }
            }

            if ($former_email != $user->getEmail()) {
                Log::warning(sprintf("UserService::update use id %s - email changed old %s - email new %s", $id, $former_email, $user->getEmail()));
                $user->clearEmailVerification();
                Event::dispatch(new UserEmailUpdated($user->getId()));
            }

            if ($former_password != $user->getPassword()) {
                Log::warning(sprintf("UserService::update use id %s - password changed", $id));
                Event::dispatch(new UserPasswordResetSuccessful($user->getId()));
            }

            return $user;
        });

        try {
            if (Config::get("queue.enable_message_broker", false) == true)
                PublishUserUpdated::dispatch($user)->onConnection('message_broker');
        } catch (\Exception $ex) {
            Log::warning($ex);
        }

        $this->addUserCRUDAction($user, $payload, "UPDATE");

        return $user;
    }

    /**
     * @param int $id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function delete(int $id): void
    {
        $this->tx_service->transaction(function () use ($id) {
            $user = $this->repository->getById($id);
            if (is_null($user) || !$user instanceof User)
                throw new EntityNotFoundException("user not found");

            try {
                if (Config::get("queue.enable_message_broker", false) == true)
                    PublishUserDeleted::dispatch($user)->onConnection('message_broker');
            } catch (\Exception $ex) {
                Log::warning($ex);
            }

            $this->repository->delete($user);

        });
    }

    /**
     * @inheritDoc
     */
    public function updateProfilePhoto($user_id, UploadedFile $file, $max_file_size = 10485760): User
    {
        $user = $this->tx_service->transaction(function () use ($user_id, $file, $max_file_size) {

            Log::debug(sprintf("UserService::updateProfilePhoto user %s", $user_id));

            $allowed_extensions = ['png', 'jpg', 'jpeg'];

            $user = $this->repository->getById($user_id);
            if (!$user instanceof User)
                throw new EntityNotFoundException("User not found.");

            $fileName = $file->getClientOriginalName();
            $fileExt = $file->extension() ?? pathinfo($fileName, PATHINFO_EXTENSION);

            Log::debug(sprintf("UserService::updateProfilePhoto user %s fileName %s fileExt %s", $user_id, $fileName, $fileExt));

            if (!in_array($fileExt, $allowed_extensions)) {
                throw new ValidationException(sprintf("File does not has a valid extension (%s).", join(",", $allowed_extensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("File exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $storage = Storage::disk(Config::get("filesystems.cloud"));
            // normalize fileName
            $fileName = FileNameSanitizer::sanitize($fileName);
            // generate path
            $path = sprintf("%s/%s", User::getProfilePicFolder(), $user->getId());
            $index = 1;
            // create unique file name
            while($storage->exists(sprintf("%s/%s", $path, $fileName))){
                Log::debug(sprintf("UserService::updateProfilePhoto user %s file %s already exists", $user_id, $fileName));
                $fileName = sprintf("%s_%s", $index++, $fileName);
                Log::debug(sprintf("UserService::updateProfilePhoto user %s new file name %s", $user_id, $fileName));
            }

            Log::debug(sprintf("UserService::updateProfilePhoto user %s saving file to swift path %s fileName %s", $user_id, $path, $fileName));
            Storage::disk(Config::get("filesystems.cloud"))->putFileAs($path, $file, $fileName, 'public');

            $user->setPic($fileName);

            return $user;
        });

        try {
            if (Config::get("queue.enable_message_broker", false) == true)
                PublishUserUpdated::dispatch($user)->onConnection('message_broker');
        } catch (\Exception $ex) {
            Log::warning($ex);
        }

        return $user;
    }

    public function notifyMonitoredSecurityGroupActivity
    (
        string $action,
        int $user_id,
        string $user_email,
        string $user_name,
        int $group_id,
        string $group_name,
        string $group_slug,
        string $action_by
    ): void
    {
       $watcher_groups = Config::get('audit.monitored_security_groups_set_activity_watchers', []);

        if (!count($watcher_groups)) {
            Log::warning("UserService::notifyMonitoredSecurityGroupActivity No monitored security groups set for activity watchers.");
            return;
        }

        $notified_users = [];
        foreach ($watcher_groups as $watcher_group_slug) {
            Log::debug(sprintf("UserService::notifyMonitoredSecurityGroupActivity processing %s", $watcher_group_slug));
            $group = $this->group_repository->getOneBySlug($watcher_group_slug);
            if(!$group instanceof Group) {
                Log::warning(sprintf("UserService::notifyMonitoredSecurityGroupActivity group %s not found", $watcher_group_slug));
                continue;
            }

            foreach($group->getUsers() as $user){
                if(in_array($user->getId(), $notified_users)){
                    continue;
                }
                $notified_users[] = $user->getId();

                Log::debug(sprintf("UserService::notifyMonitoredSecurityGroupActivity processing user %s", $user->getId()));
                Mail::queue
                (
                    new MonitoredSecurityGroupNotificationEmail
                    (
                        $user->getEmail(),
                        $action,
                        $action_by,
                        $user_id,
                        $user_email,
                        $user_name,
                        $group_id,
                        $group_name,
                        $group_slug
                    )
                );
            }
        }
    }
}