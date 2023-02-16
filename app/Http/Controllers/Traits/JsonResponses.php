<?php namespace App\Http\Controllers\Traits;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Exception;
/**
 * Trait JsonResponses
 * @package App\Http\Controllers\Traits
 */
trait JsonResponses
{
    protected function error500(Exception $ex){
        Log::error($ex);
        return Response::json(array( 'error' => 'server error'), 500);
    }

    protected function created($data='ok'){
        $res = Response::json($data, 201);
        //jsonp
        if(Request::has('callback'))
            $res->setCallback(Request::input('callback'));
        return $res;
    }

    protected function updated($data = 'ok', $has_content = true)
    {
        $res = Response::json($data, $has_content ? 201 : 204);
        //jsonp
        if (Request::has('callback')) {
            $res->setCallback(Request::input('callback'));
        }
        return $res;
    }

    protected function deleted($data='ok'){
        $res =  Response::json($data, 204);
        //jsonp
        if(Request::has('callback'))
            $res->setCallback(Request::input('callback'));
        return $res;
    }

    protected function ok($data = 'ok'){
        $res = Response::json($data, 200);
        //jsonp
        if(Request::has('callback'))
            $res->setCallback(Request::input('callback'));
        return $res;
    }

    protected function error400($data = ['message' => 'Bad Request']){
        return Response::json($data, 400);
    }

    protected function error404($data = array('message' => 'Entity Not Found')){
        if(!is_array($data)){
            $data = ['message' => $data];
        }
        return Response::json($data, 404);
    }

    protected function error403($data = array('message' => 'Forbidden'))
    {
        return Response::json($data, 403);
    }

    /**
     *  {
    "message": "Validation Failed",
    "errors": [
    {
    "resource": "Issue",
    "field": "title",
    "code": "missing_field"
    }
    ]
    }
     * @param $messages
     * @return mixed
     */
    protected function error412($messages){
        if(!is_array($messages)){
            $messages = [$messages];
        }
        return Response::json(array('message' => 'Validation Failed', 'errors' => $messages), 412);
    }
}