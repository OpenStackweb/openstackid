<?php namespace App\Jobs;
/*
 * Copyright 2025 OpenStack Foundation
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
use models\exceptions\ValidationException;
use OpenId\Services\IUserService;

/**
 * Class NotifyMonitoredSecurityGroupActivity
 * @package App\Jobs
 */
final class NotifyMonitoredSecurityGroupActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const ACTION_ADD_2_GROUP = 'Added';
    const REMOVE_FROM_GROUP = 'Removed';

    const ValidActions = [
        self::ACTION_ADD_2_GROUP,
        self::REMOVE_FROM_GROUP
    ];

    public $tries = 1;

    public $timeout = 0;

    /**
     * @var string
     */
    public $action;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $user_email;

    /*
     * @var string
     */
    public $user_name;

    /**
     * @var int
     */
    public $group_id;

    /**
     * @var string
     */
    public $group_name;

    /**
     * @var string
     */
    public $group_slug;

    public $action_by;

    /**
     * @param string $action
     * @param int $user_id
     * @param string $user_email
     * @param string $user_name
     * @param int $group_id
     * @param string $group_name
     * @param string $group_slug
     * @param string $action_by
     * @throws ValidationException
     */
    public function __construct
    (
        string $action,
        int $user_id,
        string $user_email,
        string $user_name,
        int $group_id,
        string $group_name,
        string $group_slug,
        string $action_by
    )
    {
        if(!in_array($action, self::ValidActions)){
            throw new ValidationException(sprintf("Invalid action %s, valid actions are %s", $action, implode(',', self::ValidActions)));
        }
        $this->action = $action;
        $this->user_id = $user_id;
        $this->user_email = $user_email;
        $this->user_name = $user_name;
        $this->group_id = $group_id;
        $this->group_name = $group_name;
        $this->group_slug = $group_slug;
        $this->action_by = $action_by;

        Log::debug
        (
            sprintf
            (
                "NotifyMonitoredSecurityGroupActivity::constructor action %s user_id %s user_email %s user_name %s group_id %s group_name %s group_slug %s action_by %s",
                $action,
                $user_id,
                $user_email,
                $user_name,
                $group_id,
                $group_name,
                $group_slug,
                $action_by
            )
        );
    }

    /**
     * @param IUserService $service
     * @return void
     */
    public function handle(IUserService $service){
        Log::debug
        (
            sprintf
            (
                "NotifyMonitoredSecurityGroupActivity::handle action %s user_id %s user_email %s user_name %s group_id %s group_name %s group_slug %s action_by %s",
                $this->action,
                $this->user_id,
                $this->user_email,
                $this->user_name,
                $this->group_id,
                $this->group_name,
                $this->group_slug,
                $this->action_by
            )
        );

        $service->notifyMonitoredSecurityGroupActivity
        (
            $this->action,
            $this->user_id,
            $this->user_email,
            $this->user_name,
            $this->group_id,
            $this->group_name,
            $this->group_slug,
            $this->action_by
        );
    }

    public function failed(\Throwable $exception)
    {
        Log::error(sprintf( "NotifyMonitoredSecurityGroupActivity::failed %s", $exception->getMessage()));
    }
}