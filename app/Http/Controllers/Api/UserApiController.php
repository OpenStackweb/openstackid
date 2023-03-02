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
use App\Http\Controllers\Traits\RequestProcessor;
use App\Http\Controllers\UserValidationRulesFactory;
use App\ModelSerializers\SerializerRegistry;
use Auth\Repositories\IUserRepository;
use Exception;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\Services\ITokenService;
use OpenId\Services\IUserService;
use Utils\Services\ILogService;

/**
 * Class UserApiController
 * @package App\Http\Controllers\Api
 */
final class UserApiController extends APICRUDController
{

    use RequestProcessor;

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
        ILogService     $log_service,
        IUserService    $user_service,
        ITokenService   $token_service
    )
    {
        parent::__construct($user_repository, $user_service, $log_service);
        $this->token_service = $token_service;
    }

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'first_name' => ['=@', '=='],
            'last_name' => ['=@', '=='],
            'full_name' => ['=@', '=='],
            'email' => ['=@', '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'full_name' => 'nullable|string',
            'email' => 'nullable|string',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderRules(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'identifier',
            'last_login_date',
            'spam_type'
        ];
    }

    /**
     * @param $id
     * @return mixed
     */
    public function unlock($id)
    {
        try {
            $entity = $this->service->unlockUser($id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($entity)->serialize());
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function lock($id)
    {
        try {
            $entity = $this->service->lockUser($id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($entity)->serialize());
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    protected function getAllSerializerType(): string
    {
        return SerializerRegistry::SerializerType_Private;
    }

    /**
     * @param $id
     * @param $value
     * @return mixed
     */
    public function revokeMyToken($value)
    {

        try {
            $hint = Request::input('hint', 'none');

            switch ($hint) {
                case 'access-token':
                    {
                        $this->token_service->revokeAccessToken($value, true);
                    }
                    break;
                case 'refresh-token':
                    $this->token_service->revokeRefreshToken($value, true);
                    break;
                default:
                    throw new Exception(sprintf("hint %s not allowed", $hint));
                    break;
            }
            return $this->deleted();
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return array
     */
    protected function getUpdatePayloadValidationRules(): array
    {
        return UserValidationRulesFactory::build([], true, Auth::user());
    }

    protected function curateUpdatePayload(array $payload): array
    {
        if (array_key_exists("bio", $payload)) {
            $payload["bio"] = strip_tags($payload["bio"]);
        }
        if (array_key_exists("statement_of_interest", $payload)) {
            $payload["statement_of_interest"] = strip_tags($payload["statement_of_interest"]);
        }
        return $payload;
    }

    protected function curateCreatePayload(array $payload): array
    {
        if (array_key_exists("bio", $payload)) {
            $payload["bio"] = strip_tags($payload["bio"]);
        }
        if (array_key_exists("statement_of_interest", $payload)) {
            $payload["statement_of_interest"] = strip_tags($payload["statement_of_interest"]);
        }
        return $payload;
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
        return UserValidationRulesFactory::build([], false, Auth::user());
    }

    /**
     * @param LaravelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMe()
    {
        if (!Auth::check())
            return $this->error403();

        return $this->update(Auth::user()->getId());
    }

    public function updateMyPic(){
        if (!Auth::check())
            return $this->error403();

        return $this->updatePic(Auth::user()->getId());
    }

    /**
     * @param $id
     */
    public function updatePic($id)
    {
        return $this->processRequest(function () use ($id) {
            $file = request()->file('pic');
            if (is_null($file)) {
                throw new ValidationException("pic param is required.");
            }

            $user = $this->service->updateProfilePhoto($id, $file);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($user, $this->serializerType())->serialize());
        });
    }

    protected function serializerType(): string
    {
        return SerializerRegistry::SerializerType_Private;
    }
}