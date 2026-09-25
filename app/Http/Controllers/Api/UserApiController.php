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
use App\Http\Controllers\Traits\MFACookieManager;
use App\Http\Controllers\Traits\RequestProcessor;
use App\Http\Controllers\UserValidationRulesFactory;
use App\libs\Auth\Models\UserTrustedDevice;
use App\ModelSerializers\Auth\UserTrustedDeviceSerializer;
use App\ModelSerializers\SerializerRegistry;
use App\Services\Auth\IDeviceTrustService;
use App\Services\Auth\IRecoveryCodeService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Exception;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
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

    use MFACookieManager;

    /**
     * @var ITokenService
     */
    private $token_service;

    /**
     * @var IRecoveryCodeService
     */
    private $recovery_code_service;

    /**
     * @var IDeviceTrustService
     */
    private $device_trust_service;

    /**
     * UserApiController constructor.
     * @param IUserRepository $user_repository
     * @param ILogService $log_service
     * @param IUserService $user_service
     * @param ITokenService $token_service
     * @param IRecoveryCodeService $recovery_code_service
     * @param IDeviceTrustService $device_trust_service
     */
    public function __construct
    (
        IUserRepository $user_repository,
        ILogService     $log_service,
        IUserService    $user_service,
        ITokenService   $token_service,
        IRecoveryCodeService $recovery_code_service,
        IDeviceTrustService  $device_trust_service
    )
    {
        parent::__construct($user_repository, $user_service, $log_service);
        $this->token_service = $token_service;
        $this->recovery_code_service = $recovery_code_service;
        $this->device_trust_service = $device_trust_service;
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
                        $this->token_service->revokeAccessToken($value, true, Auth::user());
                    }
                    break;
                case 'refresh-token':
                    $this->token_service->revokeRefreshToken($value, true, Auth::user());
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

    public function revokeToken($id, $value){
        return $this->processRequest(function() use($id, $value){
            $user = $this->repository->getById(intval($id));
            if(!$user instanceof User)
                throw new EntityNotFoundException();
            $this->token_service->revokeAccessToken(trim($value), true, $user);
            return $this->deleted();
        });
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

    /**
     * Enables a 2FA method for the current user and generates the first batch of
     * recovery codes for them. Plaintext codes are returned once in the response
     * and never persisted.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function enableTwoFactor()
    {
        if (!Auth::check())
            return $this->error403();

        return $this->processRequest(function () {
            $data = Request::all();
            $validator = Validator::make($data, [
                'method' => 'required|string|in:' . implode(',', User::ValidMFAMethods),
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }

            $user = Auth::user();
            $method = $data['method'];

            if ($user->isTwoFactorEnabled()) {
                return $this->error412(['method' => ['Two-factor authentication is already enabled. Use the regenerate recovery codes endpoint to rotate your codes.']]);
            }

            $codes = $this->recovery_code_service->enableTwoFactorAndGenerateCodes($user, $method);

            return $this->ok(['recovery_codes' => $codes]);
        });
    }

    /**
     * Invalidates the current user's recovery codes and generates a fresh batch.
     * Plaintext codes are returned once in the response and never persisted.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function regenerateRecoveryCodes()
    {
        if (!Auth::check())
            return $this->error403();

        return $this->processRequest(function () {
            $data = Request::all();
            $validator = Validator::make($data, [
                'current_password' => 'required|string',
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }

            $codes = $this->recovery_code_service->regenerateRecoveryCodes(Auth::user(), $data['current_password']);

            return $this->ok(['recovery_codes' => $codes]);
        });
    }

    /**
     * Lists the current user's active trusted devices. "is_current" flags the
     * device whose device-trust cookie came with this request.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getMyTrustedDevices()
    {
        if (!Auth::check())
            return $this->error403();

        return $this->processRequest(function () {
            $params = [
                UserTrustedDeviceSerializer::ParamCurrentDeviceIdentifier => $this->getCurrentDeviceIdentifier(),
            ];

            $data = array_map(
                fn(UserTrustedDevice $device) => SerializerRegistry::getInstance()
                    ->getSerializer($device)
                    ->serialize(null, [], [], $params),
                $this->device_trust_service->getActiveTrustedDevices(Auth::user())
            );

            return $this->ok(['data' => array_values($data)]);
        });
    }

    /**
     * Revokes one of the current user's trusted devices. Only the MFA bypass is
     * removed; the current session stays active.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function revokeMyTrustedDevice($id)
    {
        if (!Auth::check())
            return $this->error403();

        return $this->processRequest(function () use ($id) {
            $device = $this->device_trust_service->revokeTrustedDevice(Auth::user(), intval($id));

            if ($this->isCurrentDevice($device)) {
                $this->expireDeviceTrustCookie();
            }

            return $this->deleted();
        });
    }

    /**
     * Revokes all of the current user's active trusted devices. Only the MFA
     * bypass is removed; the current session stays active.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function revokeAllMyTrustedDevices()
    {
        if (!Auth::check())
            return $this->error403();

        return $this->processRequest(function () {
            $devices = $this->device_trust_service->removeTrustedDevices(Auth::user());

            foreach ($devices as $device) {
                if ($this->isCurrentDevice($device)) {
                    $this->expireDeviceTrustCookie();
                    break;
                }
            }

            return $this->deleted();
        });
    }

    /**
     * Hashed identifier of the device-trust cookie sent with this request, or
     * null when there is none.
     */
    private function getCurrentDeviceIdentifier(): ?string
    {
        $token = $this->getCookieToken();
        if (empty($token)) return null;
        return $this->device_trust_service->generateDeviceIdentifier($token);
    }

    private function isCurrentDevice(UserTrustedDevice $device): bool
    {
        $current = $this->getCurrentDeviceIdentifier();
        return !is_null($current) && hash_equals($device->getDeviceIdentifier(), $current);
    }

    public function revokeAllMyTokens()
    {
        if (!Auth::check())
            return $this->error403();

        $this->service->revokeAllGrantsOnSessionRevocation(Auth::user()->getId());
        return $this->deleted();
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