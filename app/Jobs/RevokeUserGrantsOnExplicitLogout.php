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
use OAuth2\Services\ITokenService;
use Utils\IPHelper;

/**
 * Class RevokeUserGrants
 * @package App\Jobs
 */
class RevokeUserGrantsOnExplicitLogout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $timeout = 0;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var string
     */
    private $client_id;


    /**
     * @param User $user
     * @param string|null $client_id
     */
    public function __construct(User $user, ?string $client_id = null){
        $this->user_id = $user->getId();
        $this->client_id = $client_id;
        Log::debug(sprintf("RevokeUserGrants::constructor user %s client id %s", $this->user_id, !empty($client_id)? $client_id :"N/A"));
    }

    public function handle(ITokenService $service){
        Log::debug(sprintf("RevokeUserGrants::handle"));
        try{
            $action = sprintf
            (
              "Revoking all grants for user %s on %s due explicit Log out.",
                $this->user_id,
                is_null($this->client_id) ? 'All Clients' : sprintf("Client %s", $this->client_id)
            );

            AddUserAction::dispatch($this->user_id, IPHelper::getUserIp(), $action);
            $service->revokeUsersToken($this->user_id, $this->client_id);
        }
        catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error(sprintf( "RevokeUserGrants::failed %s", $exception->getMessage()));
    }
}