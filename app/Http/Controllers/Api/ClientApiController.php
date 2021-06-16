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
use App\Http\Utils\PagingConstants;
use App\ModelSerializers\SerializerRegistry;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use OAuth2\Repositories\IAccessTokenRepository;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Repositories\IRefreshTokenRepository;
use OAuth2\Services\ITokenService;
use OAuth2\Services\IApiScopeService;
use OAuth2\Services\IClientService;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;
use Utils\Services\IAuthService;
use Utils\Services\ILogService;
use models\exceptions\ValidationException;
use Illuminate\Support\Facades\Log;
/**
 * Class ClientApiController
 * @package App\Http\Controllers\Api
 */
final class ClientApiController extends APICRUDController
{

    /**
     * @var IApiScopeService
     */
    private $scope_service;

    /**
     * @var ITokenService
     */
    private $token_service;

    /**
     * @var IAuthService
     */
    private $auth_service;

    /**
     * @var IAccessTokenRepository
     */
    private $access_token_repository;

    /**
     * @var IRefreshTokenRepository
     */
    private $refresh_token_repository;


    /**
     * ClientApiController constructor.
     * @param IApiScopeService $scope_service
     * @param ITokenService $token_service
     * @param IClientService $client_service
     * @param IAuthService $auth_service
     * @param ILogService $log_service
     * @param IClientRepository $client_repository
     * @param IAccessTokenRepository $access_token_repository
     * @param IRefreshTokenRepository $refresh_token_repository
     */
    public function __construct
    (
        IApiScopeService  $scope_service,
        ITokenService     $token_service,
        IClientService    $client_service,
        IAuthService      $auth_service,
        ILogService       $log_service,
        IClientRepository $client_repository,
        IAccessTokenRepository $access_token_repository,
        IRefreshTokenRepository $refresh_token_repository
    )
    {
        parent::__construct($client_repository, $client_service, $log_service);

        $this->scope_service             = $scope_service;
        $this->token_service             = $token_service;
        $this->auth_service              = $auth_service;
        $this->access_token_repository   = $access_token_repository;
        $this->refresh_token_repository  = $refresh_token_repository;
    }

    /**
     * @param $id
     * @param $scope_id
     * @return mixed
     */
    public function addAllowedScope($id, $scope_id)
    {
        try
        {
            $client = $this->service->addClientScope($id, $scope_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($client)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessages()));
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
     * @param $scope_id
     * @return mixed
     */
    public function removeAllowedScope($id, $scope_id)
    {
        try
        {
            $client = $this->service->deleteClientScope($id, $scope_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($client)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessages()));
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

    protected function applyExtraFilters(Filter $filter):Filter{
        $current_user = Auth::user();
        if(!is_null($current_user))
            $filter->addFilterCondition(FilterElement::makeEqual("user_id", $current_user->getId()));
        $filter->addFilterCondition(FilterElement::makeEqual('resource_server_not_set', true));
        return $filter;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function activate($id)
    {
        try {
            $client = $this->service->activateClient($id, true);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($client)->serialize());
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
    public function deactivate($id)
    {
        try {
            $client = $this->service->activateClient($id, false);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($client)->serialize());
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
    public function regenerateClientSecret($id)
    {
        try
        {
            $client = $this->service->regenerateClientSecret($id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($client)->serialize());
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
     * @param $use_refresh_token
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function setRefreshTokenClient($id, $use_refresh_token)
    {
        try {
            $use_refresh_token = strtolower($use_refresh_token);
            $use_refresh_token = ( $use_refresh_token == "false" || $use_refresh_token == "0") ? false : true;

            $client = $this->service->setRefreshTokenUsage($id, $use_refresh_token);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($client)->serialize());

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
     * @param $rotate_refresh_token
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function setRotateRefreshTokenPolicy($id, $rotate_refresh_token)
    {
        try {

            $rotate_refresh_token = strtolower($rotate_refresh_token);
            $rotate_refresh_token = ($rotate_refresh_token == "false" || $rotate_refresh_token == "0") ? false : true;

            $client = $this->service->setRotateRefreshTokenPolicy($id, $rotate_refresh_token);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($client)->serialize());

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
     * @param $value
     * @param $hint
     * @return mixed
     */
    public function revokeToken($id, $value, $hint)
    {
        try {
            $client = $this->repository->getClientByIdentifier($id);
            if(is_null($client))
                throw new EntityNotFoundException();

            switch ($hint) {
                case 'access-token': {
                    $token = $this->token_service->getAccessToken($value, true);
                    if (is_null($token)) {
                        throw new EntityNotFoundException();
                    }
                    if ($token->getClientId() !== $client->getClientId()) {
                        throw new ValidationException(sprintf('access token %s does not belongs to client id !', $value, $id));
                    }
                    $this->token_service->revokeAccessToken($value, true);
                }
                    break;
                case 'refresh-token': {
                    $token = $this->token_service->getRefreshToken($value, true);

                    if (is_null($token)) {
                        throw new EntityNotFoundException();
                    }

                    if ($token->getClientId() !== $client->getClientId()) {
                        throw new ValidationException(sprintf('refresh token %s does not belongs to client id !', $value, $id));
                    }
                    $this->token_service->revokeRefreshToken($value, true);
                }
                    break;
                default:
                    break;
            }

            return $this->ok();
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
    public function getAccessTokens($id)
    {
        $values = Request::all();
        $rules  = [

            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {
            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PagingConstants::DefaultPageSize;;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $client    = $this->repository->getClientByIdentifier($id);

            if(is_null($client))
                throw new EntityNotFoundException();

            $data = $this->access_token_repository->getAllValidByClientIdentifier($id, new PagingInfo($page, $per_page));

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );
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
    public function getRefreshTokens($id)
    {
        $values = Request::all();
        $rules  = [

            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {
            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PagingConstants::DefaultPageSize;;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $client    = $this->repository->getClientByIdentifier($id);

            if(is_null($client))
                throw new EntityNotFoundException();

            $data = $this->refresh_token_repository->getAllValidByClientIdentifier($id, new PagingInfo($page, $per_page));

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );
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
     * @return mixed
     */
    public function getAccessTokensByCurrentUser()
    {
        $values = Request::all();
        $rules  = [

            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {
            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PagingConstants::DefaultPageSize;;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $user      = $this->auth_service->getCurrentUser();

            $data = $this->access_token_repository->getAllValidByUserId($user->getId(), new PagingInfo($page, $per_page));
            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );
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
     * @return mixed
     */
    public function getRefreshTokensByCurrentUser()
    {
        $values = Request::all();
        $rules  = [

            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {
            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PagingConstants::DefaultPageSize;;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $user   = $this->auth_service->getCurrentUser();

            $data = $this->refresh_token_repository->getAllValidByUserId($user->getId(), new PagingInfo($page, $per_page));

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );
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
    public function unlock($id)
    {
        try {
            $client = $this->service->unlockClient($id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($client)->serialize());
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
     * @return array
     */
    protected function getUpdatePayloadValidationRules(): array
    {
        return [
            //'application_type'                => 'required|application_type',
            'app_name'                        => 'sometimes|required|freetext|max:255',
            'app_description'                 => 'sometimes|required|freetext|max:512',
            'website'                         => 'nullable|url',
            'active'                          => 'sometimes|required|boolean',
            'locked'                          => 'sometimes|required|boolean',
            'use_refresh_token'               => 'sometimes|required|boolean',
            'rotate_refresh_token'            => 'sometimes|required|boolean',
            'contacts'                        => 'nullable|email_set',
            'logo_uri'                        => 'nullable|url',
            'tos_uri'                         => 'nullable|url',
            'redirect_uris'                   => 'nullable|custom_url_set:application_type',
            'policy_uri'                      => 'nullable|url',
            'post_logout_redirect_uris'       => 'nullable|ssl_url_set',
            'allowed_origins'                 => 'nullable|ssl_url_set',
            'logout_uri'                      => 'nullable|url',
            'logout_session_required'         => 'sometimes|required|boolean',
            'logout_use_iframe'               => 'sometimes|required|boolean',
            'jwks_uri'                        => 'nullable|url',
            'default_max_age'                 => 'sometimes|required|integer',
            'require_auth_time'               => 'sometimes|required|boolean',
            'token_endpoint_auth_method'      => 'sometimes|required|token_endpoint_auth_method',
            'token_endpoint_auth_signing_alg' => 'sometimes|required|signing_alg',
            'subject_type'                    => 'sometimes|required|subject_type',
            'userinfo_signed_response_alg'    => 'sometimes|required|signing_alg',
            'userinfo_encrypted_response_alg' => 'sometimes|required|encrypted_alg',
            'userinfo_encrypted_response_enc' => 'sometimes|required|encrypted_enc',
            'id_token_signed_response_alg'    => 'sometimes|required|signing_alg',
            'id_token_encrypted_response_alg' => 'sometimes|required|encrypted_alg',
            'id_token_encrypted_response_enc' => 'sometimes|required|encrypted_enc',
            'admin_users'                     => 'nullable|int_array',
            'pkce_enabled'                    => 'sometimes|boolean',
            'otp_enabled'                     => 'sometimes|boolean',
            'otp_length'                      => 'sometimes|integer|min:4|max:8',
            'otp_lifetime'                    => 'sometimes|integer|min:60|max:600',
        ];
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
        return [
            'app_name'         => 'required|freetext|max:255',
            'app_description'  => 'required|freetext|max:512',
            'application_type' => 'required|applicationtype',
            'website'          => 'nullable|url',
            'admin_users'      => 'nullable|int_array',
        ];
    }
}