<?php namespace App\Services\Apis;
/**
 * Copyright 2020 OpenStack Foundation
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
use GuzzleHttp\Client;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use OAuth2\IResourceServerContext;
/**
 * Class RocketChatAPI
 * @package App\Services\Apis
 */
final class RocketChatAPI implements IRocketChatAPI
{

    /**
     * @var IResourceServerContext
     */
    private $resource_server_context;

    /**
     * @var string
     */
    private $base_url;

    /**
     * RocketChatAPI constructor.
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct(IResourceServerContext $resource_server_context)
    {
        $this->resource_server_context = $resource_server_context;
    }

    /**
     * @param string $service_name
     * @return array
     * @throws Exception
     */
    public function login(string $service_name): array
    {
        try {
            $client = new Client();
            $endpoint = sprintf("%s/api/v1/login", $this->base_url);

            $payload = [
                'serviceName' => $service_name,
                'accessToken' => $this->resource_server_context->getCurrentAccessToken(),
                "expiresIn" => 3600
            ];

            $response = $client->post($endpoint, [
                'json' => $payload
            ]);

            $json = $response->getBody()->getContents();
            return json_decode($json, true);
        }
        catch (ClientException $ex){
            Log::error($ex->getMessage());
            throw new ValidationException($ex->getMessage());
        }
        catch(Exception $ex){
            Log::error($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @param string $base_url
     * @return IRocketChatAPI
     */
    public function setBaseUrl(string $base_url): IRocketChatAPI
    {
        $this->base_url = $base_url;
        return $this;
    }
}