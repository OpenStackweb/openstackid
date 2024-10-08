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

use Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Utils\Services\IAuthService;

/**
 * Class PostLoginUser
 * @package App\Jobs
 */
class PostLoginUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $timeout = 0;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @param User $user
     */
    public function __construct(User $user){
        $this->user_id = $user->getId();
        Log::debug(sprintf("PostLoginUser::constructor user %s", $this->user_id));
    }

    /**
     * @param IAuthService $service
     * @return void
     */
    public function handle(IAuthService $service){
        Log::debug(sprintf("PostLoginUser::handle user %s", $this->user_id));
        try{
            $service->postLoginUserActions($this->user_id);
        }
        catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    /**
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error(sprintf( "PostLoginUser::failed %s", $exception->getMessage()));
    }
}