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
use App\Services\Auth\IRecoveryCodeService;
use App\Services\Auth\RecoveryCodesBatch;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Exception;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
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

    /**
     * @var ITokenService
     */
    private $token_service;

    /**
     * @var IRecoveryCodeService
     */
    private $recovery_code_service;

    /**
     * UserApiController constructor.
     * @param IUserRepository $user_repository
     * @param ILogService $log_service
     * @param IUserService $user_service
     * @param ITokenService $token_service
     * @param IRecoveryCodeService $recovery_code_service
     */
    public function __construct
    (
        IUserRepository $user_repository,
        ILogService     $log_service,
        IUserService    $user_service,
        ITokenService   $token_service,
        IRecoveryCodeService $recovery_code_service
    )
    {
        parent::__construct($user_repository, $user_service, $log_service);
        $this->token_service = $token_service;
        $this->recovery_code_service = $recovery_code_service;
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
        return SerializerRegistry::SerializerType_Admin;
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

            // shouldRequire2FA() covers both a stored two_factor_enabled flag and
            // group enforcement. An enforced admin must not mint a batch here without
            // the password (and would leave two_factor_enabled=true behind, keeping
            // them enrolled after leaving the group): they get codes through enroll
            // (inside the enrollment window) or regenerate (with the password).
            if ($user->shouldRequire2FA()) {
                return $this->error412(['method' => ['Two-factor authentication is already required for this account. Use the recovery codes section to set up or regenerate your codes.']]);
            }

            $batch = $this->recovery_code_service->enableTwoFactorAndGenerateCodes($user, $method);

            return $this->ok($this->batchPayload($user, $batch));
        });
    }

    /**
     * Disables 2FA for the current user (SDS idp-mfa.md §5.6). Deletes their recovery
     * codes and revokes every trusted device, so the device trust cookie is expired too.
     * Requires the current password, or for a user without one a session inside the
     * recovery enrollment window (SDS rev 2.6 §4.10.3). Refused for group-enforced users.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function disableTwoFactor()
    {
        if (!Auth::check())
            return $this->error403();

        return $this->processRequest(function () {
            $data = Request::all();
            $validator = Validator::make($data, [
                'current_password' => 'sometimes|nullable|string',
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }

            $user = Auth::user();

            if ($user->isTwoFactorEnforcedByGroup()) {
                return $this->error412(['two_factor' => ['Two-factor authentication is required for members of an administrator group and cannot be turned off.']]);
            }

            if (!$user->isTwoFactorEnabled()) {
                return $this->error412(['two_factor' => ['Two-factor authentication is not enabled for this account.']]);
            }

            // the service enforces current_password / the enrollment window for the
            // self-service actor and throws ValidationException (-> 412)
            $this->recovery_code_service->disableTwoFactor($user, $data['current_password'] ?? null, $user);

            // every trusted device was just revoked
            Cookie::queue(Cookie::forget(
                Config::get('two_factor.cookie_name', 'device_trust_token'),
                Config::get('session.path'),
                Config::get('session.domain')
            ));

            return $this->ok(['two_factor_enabled' => false]);
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
            // current_password is enforced by the service: required for a user
            // with a password; a user without one is accepted instead inside the
            // enrollment window (SDS idp-mfa.md §4.10.3).
            $validator = Validator::make($data, [
                'current_password' => 'sometimes|nullable|string',
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }

            $user = Auth::user();
            $batch = $this->recovery_code_service->regenerateRecoveryCodes($user, $data['current_password'] ?? null);

            return $this->ok($this->batchPayload($user, $batch));
        });
    }

    /**
     * Generates a recovery-code batch without the password for a user who
     * requires enrollment, right after a real 2FA challenge (SDS idp-mfa.md
     * §4.10.3). Rate limited by 2fa.rate:enroll, keyed by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function enrollRecoveryCodes()
    {
        if (!Auth::check())
            return $this->error403();

        return $this->processRequest(function () {
            $user = Auth::user();

            if (!$this->recovery_code_service->requiresRecoveryCodeEnrollment($user)) {
                return $this->error412(['recovery_codes' => ['Recovery codes enrollment is not required for this account.']]);
            }

            if (!$this->recovery_code_service->isWithinEnrollmentWindow($user)) {
                return $this->error412(['recovery_codes' => ['Your last two-factor verification is too old. Sign out and sign in again, then set up your recovery codes.']]);
            }

            $batch = $this->recovery_code_service->generateRecoveryCodes($user);

            return $this->ok($this->batchPayload($user, $batch));
        });
    }

    /**
     * Confirms that the user saved the batch they were shown ("I've Saved These
     * Codes"). The confirmation is bound to the exact batch.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function acknowledgeRecoveryCodes()
    {
        if (!Auth::check())
            return $this->error403();

        return $this->processRequest(function () {
            $validator = Validator::make(Request::all(), [
                'batch_id' => 'required|integer',
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }

            $user = Auth::user();

            if (!$this->recovery_code_service->acknowledgeRecoveryCodes($user, (int)Request::input('batch_id'))) {
                return $this->error412(['batch_id' => ['This is not your current batch of recovery codes. Generate a new one.']]);
            }

            return $this->ok($this->recovery_code_service->getStatus($user)->toArray());
        });
    }

    /**
     * 200 body of every generation endpoint: the plaintext codes, the batch
     * identifier produced by the generating transaction and the recovery-codes
     * standing (RecoveryCodesStatus keys), so the UI updates without a reload.
     */
    private function batchPayload(User $user, RecoveryCodesBatch $batch): array
    {
        return array_merge(
            [
                'recovery_codes' => $batch->getCodes(),
                'batch_id'       => $batch->getBatchId(),
            ],
            $this->recovery_code_service->getStatus($user)->toArray()
        );
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
        return SerializerRegistry::SerializerType_Admin;
    }
}