<?php namespace App\Services\Auth;
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
use App\Events\UserPasswordResetSuccessful;
use App\libs\Auth\Factories\UserFactory;
use App\libs\Auth\Factories\UserRegistrationRequestFactory;
use App\libs\Auth\Models\SpamEstimatorFeed;
use App\libs\Auth\Models\UserRegistrationRequest;
use App\libs\Auth\Repositories\IGroupRepository;
use App\libs\Auth\Repositories\ISpamEstimatorFeedRepository;
use App\libs\Auth\Repositories\IUserPasswordResetRequestRepository;
use App\libs\Auth\Repositories\IUserRegistrationRequestRepository;
use App\Mail\UserEmailVerificationRequest;
use App\Mail\UserPasswordResetRequestMail;
use App\Services\AbstractService;
use Auth\IUserNameGeneratorService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Auth\UserPasswordResetRequest;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\utils\RandomGenerator;
use OAuth2\Repositories\IClientRepository;
use Utils\Db\ITransactionService;
/**
 * Class UserService
 * @package App\Services\Auth
 */
final class UserService extends AbstractService implements IUserService
{
    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * @var IGroupRepository
     */
    private $group_repository;

    /**
     * @var IUserNameGeneratorService
     */
    private $name_generator_service;

    /**
     * @var IUserPasswordResetRequestRepository
     */
    private $request_reset_password_repository;

    /**
     * @var IUserRegistrationRequestRepository
     */
    private $user_registration_request_repository;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var ISpamEstimatorFeedRepository
     */
    private $spam_estimator_feed_repository;

    /**
     * UserService constructor.
     * @param IUserRepository $user_repository
     * @param IGroupRepository $group_repository
     * @param IUserPasswordResetRequestRepository $request_reset_password_repository
     * @param IUserRegistrationRequestRepository $user_registration_request_repository
     * @param IClientRepository $client_repository
     * @param ISpamEstimatorFeedRepository $spam_estimator_feed_repository
     * @param IUserNameGeneratorService $name_generator_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IUserRepository $user_repository,
        IGroupRepository $group_repository,
        IUserPasswordResetRequestRepository $request_reset_password_repository,
        IUserRegistrationRequestRepository $user_registration_request_repository,
        IClientRepository $client_repository,
        ISpamEstimatorFeedRepository $spam_estimator_feed_repository,
        IUserNameGeneratorService $name_generator_service,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->user_repository = $user_repository;
        $this->group_repository = $group_repository;
        $this->name_generator_service = $name_generator_service;
        $this->request_reset_password_repository = $request_reset_password_repository;
        $this->user_registration_request_repository = $user_registration_request_repository;
        $this->spam_estimator_feed_repository = $spam_estimator_feed_repository;
        $this->client_repository = $client_repository;
    }

    /**
     * @param array $payload
     * @throws ValidationException
     * @return User
     */
    public function registerUser(array $payload): User
    {
        return $this->tx_service->transaction(function() use($payload){
            $email = trim($payload['email']);
            $former_user = $this->user_repository->getByEmailOrName($email);
            if(!is_null($former_user))
                throw new ValidationException(sprintf("email %s belongs to another user !!!", $email));

            $default_groups = $this->group_repository->getDefaultOnes();
            if(count($default_groups) > 0){
                $payload['groups'] = $default_groups;
            }
            $user = UserFactory::build($payload);

            $this->user_repository->add($user);

            $formerRequest = $this->user_registration_request_repository->getByEmail($email);
            if(!is_null($formerRequest)){
                if(!$formerRequest->isRedeem()){
                    $formerRequest->redeem();
                }
            }

            return $user;
        });
    }

    /**
     * @param string $token
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return User
     */
    public function verifyEmail(string $token): User
    {
        return $this->tx_service->transaction(function() use($token){
            $user = $this->user_repository->getByVerificationEmailToken($token);
            if(is_null($user))
                throw new EntityNotFoundException();
            $user->verifyEmail();
            return $user;
        });
    }

    /**
     * @param array $payload
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return User
     */
    public function resendVerificationEmail(array $payload): User
    {
        return $this->tx_service->transaction(function() use($payload){
                $email = trim($payload['email']);
                $user = $this->user_repository->getByEmailOrName($email);
                if(is_null($user))
                    throw new EntityNotFoundException();
                return $this->sendVerificationEmail($user);
        });
    }

    /**
     * @param User $user
     * @return User
     * @throws ValidationException
     */
    public function sendVerificationEmail(User $user): User
    {
        return $this->tx_service->transaction(function() use($user){
            if($user->isEmailVerified())
                throw new ValidationException
                (
                    sprintf
                    (
                        "User %s (%s) has already verified his/her email.",
                        $user->getEmail(),
                        $user->getId()
                    )
                );

            //generate unique token
            do{
                $token = $user->generateEmailVerificationToken();
                $former_user = $this->user_repository->getByVerificationEmailToken($token);
                if(is_null($former_user)) break;
            } while(true);

           $verification_link = URL::route("verification_verify", ["token" => $token]);

           Mail::queue(new UserEmailVerificationRequest($user, $verification_link));

           return $user;
        });
    }

    /**
     * @param User $user
     * @return User
     * @throws \Exception
     */
    public function generateIdentifier(User $user): User
    {
        return $this->tx_service->transaction(function() use($user) {
            $fragment_nbr          = 1;
            $this->name_generator_service->generate($user);
            $identifier = $original_identifier = $user->getIdentifier();
            do
            {
                $old_user = $this->user_repository->getByIdentifier($identifier);
                if(!is_null($old_user))
                {
                    $identifier = $original_identifier . IUserNameGeneratorService::USER_NAME_CHAR_CONNECTOR . $fragment_nbr;
                    $fragment_nbr++;
                    continue;
                }
                $user->setIdentifier($identifier);
                break;
            } while (1);

            return $user;
        });
    }

    /**
     * @param array $payload
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return UserPasswordResetRequest
     */
    public function requestPasswordReset(array $payload): UserPasswordResetRequest
    {
        return $this->tx_service->transaction(function() use($payload) {
            $user = $this->user_repository->getByEmailOrName(trim($payload['email']));
            if(is_null($user) || !$user->isEmailVerified())
                throw new EntityNotFoundException("User not found.");

            $request = new UserPasswordResetRequest();
            $request->setOwner($user);

            do{
                $token = $request->generateToken();
                $former_request = $this->request_reset_password_repository->getByToken($token);
                if(is_null($former_request)) break;
            }while(1);

            $user->addPasswordResetRequest($request);

            $reset_link = URL::route("password.reset", ["token" => $token]);

            Mail::queue(new UserPasswordResetRequestMail($user, $reset_link));

            return $request;
        });
    }

    /**
     * @param string $token
     * @param string $new_password
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return User
     */
    public function resetPassword(string $token, string $new_password): User
    {
        return $this->tx_service->transaction(function() use($token, $new_password) {
            $request = $this->request_reset_password_repository->getByToken($token);

            if(is_null($request))
                throw new EntityNotFoundException("request not found");

            if(!$request->isValid())
                throw new ValidationException("request is void");

            if($request->isRedeem()){
                throw new ValidationException("request is already redeem");
            }

            $user = $request->getOwner();
            $user->setPassword($new_password);
            $request->redeem();
            Event::fire(new UserPasswordResetSuccessful($user->getId()));
            return $user;
        });
    }

    /**
     * @param string $client_id
     * @param array $payload
     * @return UserRegistrationRequest
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function createRegistrationRequest(string $client_id, array $payload): UserRegistrationRequest
    {
        return $this->tx_service->transaction(function() use($client_id, $payload) {

            $client      = $this->client_repository->getClientById($client_id);
            if(is_null($client))
                throw new EntityNotFoundException("client not found!");

            $email       = $payload['email'];
            $former_user = $this->user_repository->getByEmailOrName($email);

            if(!is_null($former_user))
                throw new ValidationException(sprintf("There is another user already with email %s.", $email));

            $formerRequest = $this->user_registration_request_repository->getByEmail($email);
            if(!is_null($formerRequest)){
                if($formerRequest->isRedeem()){
                    throw new ValidationException(sprintf("There is already a former registration request for email %s.", $email));
                }
                return $formerRequest;
            }
            $request = UserRegistrationRequestFactory::build($payload);
            $generator = new RandomGenerator();

            do{

                $hash = md5(
                    $request->getEmail().
                    $request->getFirstName().
                    $request->getLastName().
                    $generator->randomToken());

                $former_registration_request = $this->user_registration_request_repository->getByHash($hash);
                $request->setHash($hash);
                if(is_null($former_registration_request)) break;

            } while(1);

            $request->setClient($client);
            $this->user_registration_request_repository->add($request);
            return $request;
        });
    }

    /**
     * @param string $token
     * @param string $new_password
     * @return UserRegistrationRequest
     * @throws \Exception
     */
    public function setPassword(string $token, string $new_password): UserRegistrationRequest
    {

        return $this->tx_service->transaction(function() use($token, $new_password) {

            $request = $this->user_registration_request_repository->getByHash($token);

            if(is_null($request)) {
                Log::warning(sprintf("UserService::setPassword registration request %s not found.", $token));
                throw new EntityNotFoundException("Request not found.");
            }

            if($request->isRedeem()){
                Log::warning(sprintf("UserService::setPassword registration request %s already redeem.", $token));
                throw new ValidationException("Request is already redeem.");
            }

            $email = $request->getEmail();

            $former_user = $this->user_repository->getByEmailOrName($email);
            if(!is_null($former_user))
                throw new ValidationException(sprintf("User %s already exists!.", $email));

            $user = UserFactory::build([
                'first_name'     => $request->getFirstName(),
                'last_name'      => $request->getLastName(),
                'email'          => $email,
                'password'       => $new_password,
                'active'         => true,
                'email_verified' => true,
            ]);

            $request->setOwner($user);
            $request->redeem();
            $this->user_repository->add($user);
            Event::fire(new UserPasswordResetSuccessful($user->getId()));
            return $request;
        });
    }

    /**
     * @inheritDoc
     */
    public function recalculateUserSpamType(User $user): void
    {
        $this->tx_service->transaction(function() use($user) {
            $this->spam_estimator_feed_repository->deleteByEmail($user->getEmail());
            switch($user->getSpamType()){
                case User::SpamTypeSpam:
                        $feed = SpamEstimatorFeed::buildFromUser($user, User::SpamTypeSpam);
                        $this->spam_estimator_feed_repository->add($feed);
                    break;
                case User::SpamTypeHam:
                    $feed = SpamEstimatorFeed::buildFromUser($user, User::SpamTypeHam);
                    $this->spam_estimator_feed_repository->add($feed);
                    break;
            }
        });
    }
}