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
use App\Jobs\PublishUserCreated;
use App\libs\Auth\Factories\UserFactory;
use App\libs\Auth\Factories\UserRegistrationRequestFactory;
use App\libs\Auth\Models\SpamEstimatorFeed;
use App\libs\Auth\Models\UserRegistrationRequest;
use App\libs\Auth\Repositories\IGroupRepository;
use App\libs\Auth\Repositories\ISpamEstimatorFeedRepository;
use App\libs\Auth\Repositories\IUserPasswordResetRequestRepository;
use App\libs\Auth\Repositories\IUserRegistrationRequestRepository;
use App\Mail\UserEmailVerificationRequest;
use App\Mail\UserEmailVerificationSuccess;
use App\Mail\UserPasswordResetRequestMail;
use App\Mail\WelcomeNewUserEmail;
use App\Services\AbstractService;
use Auth\Exceptions\UserPasswordResetRequestVoidException;
use Auth\IUserNameGeneratorService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Auth\UserPasswordResetRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Models\OAuth2\OAuth2OTP;
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
     * @var IUserIdentifierGeneratorService
     */
    private $identifier_service;

    /**
     * UserService constructor.
     * @param IUserRepository $user_repository
     * @param IGroupRepository $group_repository
     * @param IUserPasswordResetRequestRepository $request_reset_password_repository
     * @param IUserRegistrationRequestRepository $user_registration_request_repository
     * @param IClientRepository $client_repository
     * @param ISpamEstimatorFeedRepository $spam_estimator_feed_repository
     * @param IUserIdentifierGeneratorService $identifier_service
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
        IUserIdentifierGeneratorService $identifier_service,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->user_repository = $user_repository;
        $this->group_repository = $group_repository;
        $this->request_reset_password_repository = $request_reset_password_repository;
        $this->user_registration_request_repository = $user_registration_request_repository;
        $this->spam_estimator_feed_repository = $spam_estimator_feed_repository;
        $this->client_repository = $client_repository;
        $this->identifier_service = $identifier_service;
    }

    /**
     * @param array $payload
     * @param OAuth2OTP|null $otp
     * @throws ValidationException
     * @return User
     */
    public function registerUser(array $payload, ?OAuth2OTP $otp = null):User
    {
        return $this->tx_service->transaction(function () use ($payload, $otp) {

            $email = trim($payload['email']);
            $former_user = $this->user_repository->getByEmailOrName($email);
            if (!is_null($former_user))
                throw new ValidationException(sprintf("email %s belongs to another user !!!", $email));

            $default_groups = $this->group_repository->getDefaultOnes();
            if (count($default_groups) > 0) {
                $payload['groups'] = $default_groups;
            }

            $user = UserFactory::build($payload);
            $this->identifier_service->generateIdentifier($user);

            if(!is_null($otp))
                $user->setCreatedByOtp($otp);

            $this->user_repository->add($user);

            $formerRequest = $this->user_registration_request_repository->getByEmail($email);

            if (!is_null($formerRequest)) {
                if (!$formerRequest->isRedeem()) {
                    $formerRequest->redeem();
                }
            }

            return $user;
        });
    }

    /**
     * @param string $token
     * @return User
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function verifyEmail(string $token): User
    {
        return $this->tx_service->transaction(function () use ($token) {
            $user = $this->user_repository->getByVerificationEmailToken($token);
            if (is_null($user))
                throw new EntityNotFoundException();
            $user->verifyEmail();
            return $user;
        });
    }

    /**
     * @param array $payload
     * @return User
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function resendVerificationEmail(array $payload): User
    {
        return $this->tx_service->transaction(function () use ($payload) {
            $email = trim($payload['email']);
            $user = $this->user_repository->getByEmailOrName($email);
            if (is_null($user))
                throw new EntityNotFoundException();
            return $this->sendVerificationEmail($user);
        });
    }

    /**
     * @param User $user
     * @return string
     */
    private function generateVerificationLink(User $user): string
    {

        return $this->tx_service->transaction(function () use ($user) {

            //generate unique token
            do {
                $token = $user->generateEmailVerificationToken();
                $former_user = $this->user_repository->getByVerificationEmailToken($token);
                if (is_null($former_user)) break;
            } while (true);

            return URL::route("verification_verify", ["token" => $token]);
        });
    }

    /**
     * @param User $user
     * @return void
     */
    public function sendWelcomeEmail(User $user): void
    {
        if(Config::get("mail.send_welcome_email")) {
            $this->tx_service->transaction(function () use ($user) {

                $reset_password_link = null;

                if (!$user->hasPasswordSet()) {
                    $request = $this->generatePasswordResetRequest($user->getEmail());
                    $reset_password_link = $request->getResetLink();
                }

                Mail::queue(new WelcomeNewUserEmail($user, $reset_password_link));
            });
        }
    }

    /**
     * @param User $user
     * @return User
     * @throws \Exception
     */
    public function sendVerificationEmail(User $user): User
    {
        return $this->tx_service->transaction(function () use ($user) {

            $verification_link = $this->generateVerificationLink($user);

            Mail::queue(new UserEmailVerificationRequest($user, $verification_link));

            return $user;
        });
    }

    /**
     * @param string $email
     * @return UserPasswordResetRequest
     * @throws \Exception
     */
    public function generatePasswordResetRequest(string $email): UserPasswordResetRequest
    {
        return $this->tx_service->transaction(function () use ($email) {

            $user = $this->user_repository->getByEmailOrName(trim($email));
            if (is_null($user) || !$user->isEmailVerified())
                throw new EntityNotFoundException("User not found.");

            $request = new UserPasswordResetRequest();
            $request->setOwner($user);

            do {
                $token = $request->generateToken();
                $former_request = $this->request_reset_password_repository->getByToken($token);
                if (is_null($former_request)) break;
            } while (1);

            $user->addPasswordResetRequest($request);

            $reset_link = URL::route("password.reset", ["token" => $token]);

            $request->setResetLink($reset_link);

            return $request;
        });
    }

    /**
     * @param array $payload
     * @return UserPasswordResetRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function requestPasswordReset(array $payload): UserPasswordResetRequest
    {
        $request = $this->generatePasswordResetRequest(trim($payload['email']));

        return $this->tx_service->transaction(function () use ($payload) {

            $user = $this->user_repository->getByEmailOrName(trim($payload['email']));
            if (is_null($user) || !$user->isEmailVerified())
                throw new EntityNotFoundException("User not found.");

            $request = $this->generatePasswordResetRequest($user->getEmail());

            Mail::queue(new UserPasswordResetRequestMail($user, $request->getResetLink()));

            return $request;
        });
    }

    /**
     * @param string $token
     * @param string $new_password
     * @return User
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function resetPassword(string $token, string $new_password): User
    {
        return $this->tx_service->transaction(function () use ($token, $new_password) {
            $request = $this->request_reset_password_repository->getByToken($token);

            if (is_null($request))
                throw new EntityNotFoundException("request not found");

            if (!$request->isValid())
                throw new UserPasswordResetRequestVoidException("request is void");

            if ($request->isRedeem()) {
                throw new ValidationException("request is already redeem");
            }

            $user = $request->getOwner();
            $user->setPassword($new_password);
            $request->redeem();
            Event::dispatch(new UserPasswordResetSuccessful($user->getId()));
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
        return $this->tx_service->transaction(function () use ($client_id, $payload) {

            $client = $this->client_repository->getClientById($client_id);
            if (is_null($client))
                throw new EntityNotFoundException("client not found!");

            $email = $payload['email'];
            $former_user = $this->user_repository->getByEmailOrName($email);

            if (!is_null($former_user))
                throw new ValidationException(sprintf("There is another user already with email %s.", $email));

            $formerRequest = $this->user_registration_request_repository->getByEmail($email);
            if (!is_null($formerRequest)) {
                if (!$formerRequest->isRedeem()) {
                    return UserRegistrationRequestFactory::populate($formerRequest, $payload);
                }
                $this->user_registration_request_repository->delete($formerRequest);
            }

            $request = UserRegistrationRequestFactory::build($payload);
            $generator = new RandomGenerator();

            do {

                $hash = md5(
                    $request->getEmail() .
                    $request->getFirstName() .
                    $request->getLastName() .
                    $request->getCompany().
                    $generator->randomToken());

                $former_registration_request = $this->user_registration_request_repository->getByHash($hash);
                $request->setHash($hash);
                if (is_null($former_registration_request)) break;

            } while (1);

            $request->setClient($client);
            $this->user_registration_request_repository->add($request);
            return $request;
        });
    }

    /**
     * @param int $id
     * @param array $payload
     * @return UserRegistrationRequest
     * @throws \Exception
     */
    public function updateRegistrationRequest(int $id, array $payload):UserRegistrationRequest{
        return $this->tx_service->transaction(function () use ($id, $payload) {

            $formerRequest = $this->user_registration_request_repository->getById($id);
            if (is_null($formerRequest) || !$formerRequest instanceof UserRegistrationRequest) {
                 throw new EntityNotFoundException(sprintf("User Registration Request %s not found", $id));
            }

            if ($formerRequest->isRedeem()) {
                throw new ValidationException(sprintf("User Registration Request %s is already redeemed.", $id));
            }

            return UserRegistrationRequestFactory::populate($formerRequest, $payload);
        });
    }

    /**
     * @param string $token
     * @param string $new_password
     * @param array $payload
     * @return UserRegistrationRequest
     * @throws \Exception
     */
    public function setPassword(string $token, string $new_password, array $payload = []): UserRegistrationRequest
    {

        return $this->tx_service->transaction(function () use ($token, $new_password, $payload) {

            $request = $this->user_registration_request_repository->getByHash($token);

            if (is_null($request)) {
                Log::warning(sprintf("UserService::setPassword registration request %s not found.", $token));
                throw new EntityNotFoundException("Request not found.");
            }

            if ($request->isRedeem()) {
                Log::warning(sprintf("UserService::setPassword registration request %s already redeem.", $token));
                throw new ValidationException("Request is already redeem.");
            }

            $email = $request->getEmail();

            $former_user = $this->user_repository->getByEmailOrName($email);
            if (!is_null($former_user))
                throw new ValidationException(sprintf("User %s already exists!.", $email));

            $user = UserFactory::build([
                'first_name' => isset($payload['first_name']) && !empty($payload['first_name']) ? trim($payload['first_name']): $request->getFirstName(),
                'last_name' =>  isset($payload['last_name']) &&  !empty($payload['last_name']) ? trim($payload['last_name']) : $request->getLastName(),
                'company' => isset($payload['company']) && !empty($payload['company']) ? trim($payload['company']) : $request->getCompany(),
                'country_iso_code' => isset($payload['country_iso_code']) && !empty($payload['country_iso_code']) ? trim($payload['country_iso_code']) : '',
                'email' => $email,
                'password' => $new_password,
                'email_verified' => true,
            ]);

            $this->identifier_service->generateIdentifier($user);
            $request->setOwner($user);
            $request->redeem();
            $this->user_repository->add($user);
            Event::dispatch(new UserPasswordResetSuccessful($user->getId()));
            return $request;
        });
    }

    /**
     * @inheritDoc
     */
    public function recalculateUserSpamType(User $user): void
    {
        $this->tx_service->transaction(function () use ($user) {
            $this->spam_estimator_feed_repository->deleteByEmail($user->getEmail());
            switch ($user->getSpamType()) {
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

    /**
     * @param int $user_id
     * @return User|null
     * @throws \Exception
     */
    public function sendSuccessfulVerificationEmail(int $user_id): ?User
    {
        return $this->tx_service->transaction(function() use($user_id){

            $user = $this->user_repository->getById($user_id);
            if(is_null($user) || !$user instanceof User) return null;

            $reset_password_link = null;

            if(!$user->hasPasswordSet()){
                $service = App::make(IUserService::class);
                $request = $service->generatePasswordResetRequest($user->getEmail());
                $reset_password_link = $request->getResetLink();
            }

            Mail::queue(new UserEmailVerificationSuccess($user, $reset_password_link));

            $this->sendWelcomeEmail($user);

            return $user;
        });
    }

    /**
     * @param int $user_id
     * @return User|null
     * @throws \Exception
     */
    public function initializeUser(int $user_id): ?User
    {
        return $this->tx_service->transaction(function() use($user_id) {

            $user = $this->user_repository->getById($user_id);
            if(is_null($user) || !$user instanceof User) return null;

            if(!$user->isEmailVerified()) {
                $this->sendVerificationEmail($user);
                return $user;
            }

            // email is already verified

            $this->sendWelcomeEmail($user);

            try {
                if(Config::get("queue.enable_message_broker", false) == true)
                    PublishUserCreated::dispatch($user)->onConnection('message_broker');
            }
            catch (\Exception $ex){
                Log::warning($ex);
            }

            return $user;
        });
    }
}