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
use App\libs\Auth\Models\UserRegistrationRequest;
use Auth\User;
use Auth\UserPasswordResetRequest;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Models\OAuth2\OAuth2OTP;

/**
 * Interface IUserService
 * @package App\Services\Auth
 */
interface IUserService
{
    /**
     * @param array $payload
     * @param OAuth2OTP|null $otp
     * @throws ValidationException
     * @return User
     */
    public function registerUser(array $payload, ?OAuth2OTP $otp = null):User;

    /**
     * @param string $token
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return User
     */
    public function verifyEmail(string $token):User;

    /**
     * @param array $payload
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return User
     */
    public function resendVerificationEmail(array $payload):User;

    /**
     * @param User $user
     * @throws ValidationException
     * @return User
     */
    public function sendVerificationEmail(User $user): User;

    /**
     * @param array $payload
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return UserPasswordResetRequest
     */
    public function requestPasswordReset(array $payload):UserPasswordResetRequest;

    /**
     * @param string $token
     * @param string $new_password
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return User
     */
    public function resetPassword(string $token, string $new_password):User;

    /**
     * @param string $token
     * @param string $new_password
     * @param array $payload
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return UserRegistrationRequest
     */
    public function setPassword(string $token, string $new_password, array $payload = []):UserRegistrationRequest;

    /**
     * @param string $client_id
     * @param array $payload
     * @return UserRegistrationRequest
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function createRegistrationRequest(string $client_id, array $payload):UserRegistrationRequest;

    /**
     * @param User $user
     * @return void
     */
    public function recalculateUserSpamType(User $user):void;

    /**
     * @param User $user
     * @return void
     */
    public function sendWelcomeEmail(User $user):void;

    /**
     * @param string $email
     * @return UserPasswordResetRequest
     * @throws \Exception
     */
    public function generatePasswordResetRequest(string $email):UserPasswordResetRequest;

    /**
     * @param int $user_id
     * @return User|null
     */
    public function sendSuccessfulVerificationEmail(int $user_id):?User;

    /**
     * @param int $user_id
     * @return User|null
     */
    public function initializeUser(int $user_id):?User;

    /**
     * @param int $id
     * @param array $payload
     * @return UserRegistrationRequest
     * @throws \Exception
     */
    public function updateRegistrationRequest(int $id, array $payload):UserRegistrationRequest;

    /**
     * @param int $user_id
     * @return void
     * @throws \Exception
     */
    public function sendOTPRegistrationReminder(int $user_id);
}