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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Services\IUserActionService;

/**
 * Class AddUserAction
 * @package App\Jobs
 */
final class AddUserAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 1;

    public $timeout = 0;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var string
     */
    private $action;

    /**
     * @var string
     */
    private $ip;

    /**
     * @param int $user_id
     * @param string $ip
     * @param string $action
     */
    public function __construct(int $user_id, string $ip, string $action){
        Log::debug(sprintf("AddUserAction::constructor user %s action %s ip %s", $user_id, $action, $ip));
        $this->user_id = $user_id;
        $this->action = $action;
        $this->ip = $ip;
    }

    public function handle(IUserActionService $service){
        Log::debug(sprintf("AddUserAction::handle"));
        try{
            $service->addUserAction($this->user_id, $this->ip, $this->action);
        }
        catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error(sprintf( "AddUserAction::failed %s", $exception->getMessage()));
    }
}