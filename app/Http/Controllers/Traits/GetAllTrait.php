<?php namespace App\Http\Controllers;
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
use App\Http\Utils\PagingConstants;
use App\ModelSerializers\SerializerRegistry;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use utils\Filter;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use Exception;
use models\exceptions\ValidationException;
use models\exceptions\EntityNotFoundException;
/**
 * Trait GetAllTrait
 * @package App\Http\Controllers
 */
trait GetAllTrait
{
    /**
     * @return array
     */
    protected function getFilterRules():array{
        return [];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [];
    }

    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [];
    }

    protected function applyExtraFilters(Filter $filter):Filter{
        return $filter;
    }

    protected function getAllSerializerType():string{
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     * @return mixed
     */
    public function getAll()
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
                $filter = FilterParser::parse(Input::get('filter'), $this->getFilterRules());
            }

            if(is_null($filter)) $filter = new Filter();

            $filter_validator_rules = $this->getFilterValidatorRules();
            if(count($filter_validator_rules)) {
                $filter->validate($filter_validator_rules);
            }

            $order = null;

            if (Input::has('order'))
            {
                $order = OrderParser::parse(Input::get('order'), $this->getOrderRules());
            }

            $data = $this->repository->getAllByPage(new PagingInfo($page, $per_page), $this->applyExtraFilters($filter), $order);

            return $this->ok
            (
                $data->toArray
                (
                    Input::get('expand', ''),
                    [],
                    [],
                    [],
                    $this->getAllSerializerType()
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