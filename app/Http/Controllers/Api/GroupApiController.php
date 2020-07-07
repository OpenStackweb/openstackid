<?php namespace App\Http\Controllers\Api;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\libs\Auth\Repositories\IGroupRepository;
use App\ModelSerializers\SerializerRegistry;
use App\Services\Auth\IGroupService;
use Auth\Repositories\IUserRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\OrderParser;
use Utils\Services\ILogService;
use utils\PagingInfo;
use Exception;
/**
 * Class GroupApiController
 * @package App\Http\Controllers\Api
 */
final class GroupApiController extends APICRUDController
{
    /**
     * @var IUserRepository
     */
    private $user_repository;

    public function __construct
    (
        IGroupRepository $repository,
        IUserRepository $user_repository,
        IGroupService $service,
        ILogService $log_service
    )
    {
        parent::__construct($repository, $service, $log_service);
        $this->user_repository = $user_repository;
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'name'     => ['=@', '=='],
            'slug'     => ['=@', '=='],
            'active'   => [ '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array
    {
        return [
            'name'     => 'sometimes|required|string',
            'slug'     => 'sometimes|required|string',
            'active'   => 'sometimes|required|boolean',
        ];
    }

    /**
     * @return array
     */
    protected function getUpdatePayloadValidationRules(): array
    {
        return [
            'name'    => 'sometimes|required|string|max:512',
            'slug'    => 'sometimes|alpha_dash|string|max:254',
            'active'  => 'sometimes|required|boolean',
            'default' => 'sometimes|required|boolean',
        ];
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {

        return [
            'name'    => 'required|string|max:512',
            'slug'    => 'required|alpha_dash|max:254',
            'active'  => 'required|boolean',
            'default' => 'required|boolean',
        ];
    }

    /**
     * @param $group_id
     * @param $user_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addUserToGroup($group_id, $user_id){
        try {
            $group = $this->repository->getById($group_id);
            if(is_null($group))
                return $this->error404();
            $this->service->addUser2Group($group, $user_id);
            return $this->updated();
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

    /**
     * @param $group_id
     * @param $user_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeUserFromGroup($group_id, $user_id){
        try {
            $group = $this->repository->getById($group_id);
            if(is_null($group))
                return $this->error404();
            $this->service->removeUserFromGroup($group, $user_id);
            return $this->deleted();
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

    /**
     * @param $group_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getUsersFromGroup($group_id)
    {
        $values = Input::all();
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

            if (Input::has('page')) {
                $page     = intval(Input::get('page'));
                $per_page = intval(Input::get('per_page'));
            }

            $filter = null;

            if (Input::has('filter')) {
                $filter = FilterParser::parse(Input::get('filter'), [
                    'first_name'     => ['=@', '=='],
                    'last_name'      => ['=@', '=='],
                    'email'          => ['=@', '=='],
                    'full_name'      => ['=@', '=='],
                ]);
            }

            if(is_null($filter)) $filter = new Filter();

            $filter_validator_rules = [
                'first_name'     => 'nullable|string',
                'last_name'      => 'nullable|string',
                'email'          => 'nullable|string',
                'full_name'      => 'nullable|string',
            ];

            if(count($filter_validator_rules)) {
                $filter->validate($filter_validator_rules);
            }

            $order = null;

            if (Input::has('order'))
            {
                $order = OrderParser::parse(Input::get('order'), [

                ]);
            }

            $filter->addFilterCondition(FilterElement::makeEqual("group_id", $group_id));

            $data = $this->user_repository->getAllByPage(new PagingInfo($page, $per_page), $filter, $order);

            return $this->ok
            (
                $data->toArray
                (
                    Input::get('expand', ''),
                    [],
                    [],
                    [],
                    SerializerRegistry::SerializerType_Private
                )
            );
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

}