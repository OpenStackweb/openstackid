<?php namespace App\Strategies\OTP;
/**
 * Copyright 2021 OpenStack Foundation
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

use App\Mail\OAuth2PasswordlessOTPMail;
use App\Services\Auth\IUserService;
use Auth\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Models\OAuth2\OAuth2OTP;
use Auth\Repositories\IUserRepository;
/**
 * Class OTPChannelEmailStrategy
 * @package App\Strategies\OTP
 */
final class OTPChannelEmailStrategy
implements IOTPChannelStrategy
{
    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * @var IUserService
     */
    private $user_service;

    /**
     * @param IUserService $user_service
     * @param IUserRepository $user_repository
     */
    public function __construct
    (
        IUserService $user_service,
        IUserRepository $user_repository
    )
    {
        $this->user_repository = $user_repository;
        $this->user_service = $user_service;
    }
    /**
     * @param IOTPTypeBuilderStrategy $typeBuilderStrategy
     * @param OAuth2OTP $otp
     * @return bool
     */
    public function send(IOTPTypeBuilderStrategy $typeBuilderStrategy, OAuth2OTP $otp): bool
    {
        $value = $typeBuilderStrategy->generate($otp);
        // send email
        try{
            $reset_password_link = null;
            $user = $this->user_repository->getByEmailOrName($otp->getUserName());
            if($user instanceof User && !$user->hasPasswordSet()){
                // create a password reset request
                Log::debug
                (
                    sprintf
                    (
                        "OTPChannelEmailStrategy::send user %s has no password set",
                        $user->getId()
                    )
                );
                $request =  $this->user_service->generatePasswordResetRequest($user->getEmail());
                $reset_password_link = $request->getResetLink();
            }

            Mail::queue
            (
                new OAuth2PasswordlessOTPMail
                (
                    $otp->getUserName(),
                    $value,
                    $otp->getLifetime(),
                    $reset_password_link,
                    $otp->hasClient() ? $otp->getClient() : null
                )
            );
        }
        catch (\Exception $ex){
            Log::error($ex);
            return false;
        }
        return true;
    }
}