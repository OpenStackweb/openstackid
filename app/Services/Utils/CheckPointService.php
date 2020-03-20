<?php namespace Services\Utils;
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
use App\libs\Auth\Repositories\IUserExceptionTrailRepository;
use Auth\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Utils\Db\ITransactionService;
use Utils\Services\ICheckPointService;
use Utils\Services\ISecurityPolicy;
use Utils\IPHelper;
use Models\UserExceptionTrail;
/**
 * Class CheckPointService
 * @package Services\Utils
 */
class CheckPointService implements ICheckPointService
{
    /**
     * @var ISecurityPolicy[]
     */
    private $policies;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @var IUserExceptionTrailRepository
     */
    private $user_exception_trail_repository;

    /**
     * CheckPointService constructor.
     * @param IUserExceptionTrailRepository $user_exception_trail_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IUserExceptionTrailRepository $user_exception_trail_repository,
        ITransactionService $tx_service
    )
    {
        $this->policies = [];
        $this->tx_service = $tx_service;
        $this->user_exception_trail_repository = $user_exception_trail_repository;
    }

    public function check()
    {
        $res = false;
        try {
            foreach ($this->policies as $policy) {
                $res = $policy->check();
                if (!$res) break;
            }
        } catch (Exception $ex) {
            Log::error($ex);
        }
        return $res;
    }

    /**
     * @param Exception $ex
     * @throws Exception
     */
    public function trackException(Exception $ex):void
    {
        $this->tx_service->transaction(function() use($ex){
            try {
                $remote_ip                  = IPHelper::getUserIp();
                $class_name                 = get_class($ex);
                $user_trail                 = new UserExceptionTrail();
                $user_trail->setFromIp($remote_ip);
                $user_trail->setExceptionType($class_name);
                $user_trail->setStackTrace($ex->getTraceAsString());
                if(Auth::check()){
                    $currentUser = Auth::user();
                    if($currentUser instanceof User && !$currentUser->isNew())
                        $user_trail->setUser($currentUser);
                }
                $this->user_exception_trail_repository->add($user_trail, true);

                Log::warning(sprintf("* CheckPointService - exception : << %s >> - IP Address: %s",$ex->getMessage(),$remote_ip));
                //applying policies
                foreach ($this->policies as $policy) {
                    $policy->apply($ex);
                }
            } catch (Exception $ex) {
                Log::error($ex);
            }
        });
    }

    /**
     * @param ISecurityPolicy $policy
     * @return $this
     */
    public function addPolicy(ISecurityPolicy $policy)
    {
        $this->policies[] = $policy;
        return $this;
    }
}
