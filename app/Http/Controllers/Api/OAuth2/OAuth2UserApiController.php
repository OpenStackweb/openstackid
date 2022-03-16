<?php namespace App\Http\Controllers\Api\OAuth2;
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

use App\Http\Controllers\GetAllTrait;
use App\Http\Controllers\UserValidationRulesFactory;
use App\Http\Utils\HTMLCleaner;
use App\ModelSerializers\SerializerRegistry;
use Auth\Repositories\IUserRepository;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\Builders\IdTokenBuilder;
use OAuth2\IResourceServerContext;
use OAuth2\Repositories\IClientRepository;
use OAuth2\ResourceServer\IUserService;
use Utils\Http\HttpContentType;
use Utils\Services\ILogService;
use Exception;
use OpenId\Services\IUserService as IOpenIdUserService;
/**
 * Class OAuth2UserApiController
 * @package App\Http\Controllers\Api\OAuth2
 */
final class OAuth2UserApiController extends OAuth2ProtectedController
{
    use GetAllTrait;

    protected function getAllSerializerType(): string
    {
        return SerializerRegistry::SerializerType_Private;
    }

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'first_name' => ['=@', '=='],
            'last_name' => ['=@', '=='],
            'email' => ['=@', '=='],
            'primary_email' => ['=@', '=='],
        ];
    }

    public function getOrderRules(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'first_name' => 'sometimes|required|string',
            'last_name' => 'sometimes|required|string',
            'email' => 'sometimes|required|string',
            'primary_email' => 'sometimes|required|string',
        ];
    }

    /**
     * @var IUserService
     */
    private $user_service;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var IdTokenBuilder
     */
    private $id_token_builder;

    /**
     * @var IOpenIdUserService
     */
    private $openid_user_service;


    /**
     * OAuth2UserApiController constructor.
     * @param IUserRepository $repository
     * @param IUserService $user_service
     * @param IResourceServerContext $resource_server_context
     * @param ILogService $log_service
     * @param IOpenIdUserService $openid_user_service
     * @param IClientRepository $client_repository
     * @param IdTokenBuilder $id_token_builder
     */
    public function __construct
    (
        IUserRepository $repository,
        IUserService $user_service,
        IResourceServerContext $resource_server_context,
        ILogService $log_service,
        IOpenIdUserService $openid_user_service,
        IClientRepository $client_repository,
        IdTokenBuilder $id_token_builder
    )
    {
        parent::__construct($resource_server_context, $log_service);
        $this->repository = $repository;
        $this->user_service = $user_service;
        $this->client_repository = $client_repository;
        $this->id_token_builder = $id_token_builder;
        $this->openid_user_service = $openid_user_service;
    }

    /**
     * Gets User Basic Info
     * @return mixed
     */
    public function me()
    {
        try {
            $data = $this->user_service->getCurrentUserInfo();
            return $this->ok($data);
        } catch (Exception $ex) {
            $this->log_service->error($ex);
            return $this->error500($ex);
        }
    }

    protected function curateUpdatePayload(array $payload): array
    {
        // remove possible fields that an user can not update
        // from this endpoint
        if(isset($payload['groups']))
            unset($payload['groups']);

        if(isset($payload['email_verified']))
            unset($payload['email_verified']);

        if(isset($payload['active']))
            unset($payload['active']);

        return HTMLCleaner::cleanData($payload, [
            'bio', 'statement_of_interest'
        ]);
    }

    private function _update($id){
        try {

            if(!Request::isJson()) return $this->error400();

            $payload = Request::json()->all();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, UserValidationRulesFactory::build($payload, true, Auth::user()));
            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            $user = $this->openid_user_service->update($id, $this->curateUpdatePayload($payload));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($user, SerializerRegistry::SerializerType_Private)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function updateMe(){
        return $this->_update($this->resource_server_context->getCurrentUserId());
    }

    public function update($id){
       return $this->_update($id);
    }

    public function updateMyPic(LaravelRequest $request){
        try {
            if (!$this->resource_server_context->getCurrentUserId()) {
                return $this->error403();
            }

            $file = $request->hasFile('file') ? $request->file('file'):null;
            if(is_null($file)){
                throw new ValidationException('file is not present');
            }
            $user = $this->openid_user_service->updateProfilePhoto($this->resource_server_context->getCurrentUserId(), $file);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($user, SerializerRegistry::SerializerType_Private)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function userInfo()
    {
        try {
            $claims = $this->user_service->getCurrentUserInfoClaims();
            $client_id = $this->resource_server_context->getCurrentClientId();
            $client = $this->client_repository->getClientById($client_id);

            // The UserInfo Claims MUST be returned as the members of a JSON object unless a signed or encrypted response
            // was requested during Client Registration.
            $user_info_response_info = $client->getUserInfoResponseInfo();

            $sig_alg = $user_info_response_info->getSigningAlgorithm();
            $enc_alg = $user_info_response_info->getEncryptionKeyAlgorithm();
            $enc = $user_info_response_info->getEncryptionContentAlgorithm();

            if ($sig_alg || ($enc_alg && $enc)) {
                $jwt = $this->id_token_builder->buildJWT($claims, $user_info_response_info, $client);
                $http_response = Response::make($jwt->toCompactSerialization(), 200);
                $http_response->header('Content-Type', HttpContentType::JWT);
                $http_response->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
                $http_response->header('Pragma', 'no-cache');
                return $http_response;
            } else {
                // return plain json
                return $this->ok($claims->toArray());
            }
        } catch (Exception $ex) {
            $this->log_service->error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function get($id)
    {
        try {
            $user = $this->repository->getById(intval($id));
            if (is_null($user)) {
                throw new EntityNotFoundException();
            }
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($user, SerializerRegistry::SerializerType_Private)->serialize());
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

}