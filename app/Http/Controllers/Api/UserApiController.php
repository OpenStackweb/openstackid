<?php namespace App\Http\Controllers\Api;
/**
 * Copyright 2015 OpenStack Foundation
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
use App\Http\Controllers\APICRUDController;
use App\Http\Utils\HTMLCleaner;
use App\ModelSerializers\SerializerRegistry;
use Auth\Repositories\IUserRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use OAuth2\Services\ITokenService;
use OpenId\Services\IUserService;
use models\exceptions\EntityNotFoundException;
use Utils\Services\ILogService;
/**
 * Class UserApiController
 * @package App\Http\Controllers\Api
 */
final class UserApiController extends APICRUDController {

    /**
     * @var ITokenService
     */
    private $token_service;

    /**
     * UserApiController constructor.
     * @param IUserRepository $user_repository
     * @param ILogService $log_service
     * @param IUserService $user_service
     * @param ITokenService $token_service
     */
    public function __construct
    (
        IUserRepository $user_repository,
        ILogService $log_service,
        IUserService $user_service,
        ITokenService $token_service
    ){
        parent::__construct($user_repository, $user_service, $log_service);
        $this->token_service = $token_service;
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'first_name'     => ['=@', '=='],
            'last_name'      => ['=@', '=='],
            'full_name'      => ['=@', '=='],
            'email'          => ['=@', '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array
    {
        return [
            'first_name'     => 'nullable|string',
            'last_name'      => 'nullable|string',
            'full_name'      => 'nullable|string',
            'email'          => 'nullable|string',
        ];
    }


    /**
     * @param $id
     * @return mixed
     */
    public function unlock($id){
        try {
            $entity = $this->service->unlockUser($id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($entity)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function lock($id){
        try {
            $entity = $this->service->lockUser($id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($entity)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    protected function getAllSerializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }

    /**
     * @param $id
     * @param $value
     * @return mixed
     */
    public function revokeMyToken($value){

        try{
            $hint = Input::get('hint','none');

            switch($hint){
                case 'access-token':{
                    $this->token_service->revokeAccessToken($value,true);
                }
                break;
                case 'refresh-token':
                    $this->token_service->revokeRefreshToken($value,true);
                    break;
                default:
                    throw new Exception(sprintf("hint %s not allowed",$hint));
                    break;
            }
            return $this->deleted();
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return array
     */
    protected function getUpdatePayloadValidationRules(): array
    {
        return [
            'first_name'             => 'required|string',
            'last_name'              => 'required|string',
            'email'                  => 'required|email',
            'identifier'             => 'sometimes|string',
            'bio'                    => 'nullable|string',
            'address1'               => 'nullable|string',
            'address2'               => 'nullable|string',
            'city'                   => 'nullable|string',
            'state'                  => 'nullable|string',
            'post_code'              => 'nullable|string',
            'country_iso_code'       => 'nullable|country_iso_alpha2_code',
            'second_email'           => 'nullable|email',
            'third_email'            => 'nullable|email',
            'gender'                 => 'nullable|string',
            'gender_specify'         => 'nullable|string',
            'statement_of_interest'  => 'nullable|string',
            'irc'                    => 'nullable|string',
            'linked_in_profile'      => 'nullable|string',
            'github_user'            => 'nullable|string',
            'wechat_user'            => 'nullable|string',
            'twitter_name'           => 'nullable|string',
            'language'               => 'nullable|string',
            'birthday'               => 'nullable|date_format:U',
            'password'               => 'sometimes|string|min:8|confirmed',
            'email_verified'         => 'nullable|boolean',
            'active'                 => 'nullable|boolean'
        ];
    }

    protected function curateUpdatePayload(array $payload):array {
        return HTMLCleaner::cleanData($payload, [
            'bio', 'statement_of_interest'
        ]);
    }

    protected function curateCreatePayload(array $payload):array {
        return HTMLCleaner::cleanData($payload, [
            'bio', 'statement_of_interest'
        ]);
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
        return [
            'first_name'             => 'required|string',
            'last_name'              => 'required|string',
            'email'                  => 'required|email',
            'identifier'             => 'sometimes|string',
            'bio'                    => 'nullable|string',
            'address1'               => 'nullable|string',
            'address2'               => 'nullable|string',
            'city'                   => 'nullable|string',
            'state'                  => 'nullable|string',
            'post_code'              => 'nullable|string',
            'country_iso_code'       => 'nullable|country_iso_alpha2_code',
            'second_email'           => 'nullable|email',
            'third_email'            => 'nullable|email',
            'gender'                 => 'nullable|string',
            'statement_of_interest'  => 'nullable|string',
            'irc'                    => 'nullable|string',
            'linked_in_profile'      => 'nullable|string',
            'github_user'            => 'nullable|string',
            'wechat_user'            => 'nullable|string',
            'twitter_name'           => 'nullable|string',
            'language'               => 'nullable|string',
            'birthday'               => 'nullable|date_format:U',
            'password'               => 'sometimes|string|min:8|confirmed',
            'email_verified'         => 'nullable|boolean',
            'active'                 => 'nullable|boolean'
        ];
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMe(){
        if(!Auth::check())
            return $this->error403();
        $myId = Auth::user()->getId();
        return $this->update($myId);
    }

    protected function serializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }
}