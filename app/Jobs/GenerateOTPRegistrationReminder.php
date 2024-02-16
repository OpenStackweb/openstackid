<?php namespace App\Jobs;
/*
 * Copyright 2024 OpenStack Foundation
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

use App\Services\Auth\IUserService as IAuthUserService;
use Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class GenerateOTPRegistrationReminder
 * @package App\Jobs
 */
final class GenerateOTPRegistrationReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user_id;

    public function __construct(User $user)
    {
        $this->user_id = $user->getId();
        Log::debug(sprintf("GenerateOTPRegistrationReminder::GenerateOTPRegistrationReminder user %s", $user->getEmail()));
    }

    /**
     * @param IAuthUserService $service
     * @return void
     * @throws \Exception
     */
    public function handle(IAuthUserService $service)
    {
        Log::debug(sprintf("GenerateOTPRegistrationReminder::handle user %s", $this->user_id));
        $service->sendOTPRegistrationReminder($this->user_id);
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}