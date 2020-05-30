<?php
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
use Models\OpenId\ServerExtension;
use Models\OAuth2\ApiEndpoint;
use Models\OAuth2\ApiScope;
use Models\OAuth2\Api;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\ResourceServer;
/**
 * Class SeedUtils
 */
final class SeedUtils
{
    /**
     * @param string $api_name
     * @param string $api_description
     * @return bool
     */
    public static function seedApi(string $api_name, string $api_description){
        $resource_server_repository = EntityManager::getRepository(ResourceServer::class);
        $rs = $resource_server_repository->find(1);
        if(is_null($rs)) return false;
        $api = new Api();
        $api->setName($api_name);
        $api->setActive(true);
        $api->setDescription($api_description);
        $api->setResourceServer($rs);

        EntityManager::persist($api);

        EntityManager::flush();

        return true;
    }

    /**
     * @param string $api_name
     * @param array $endpoints_info
     */
    public static function seedApiEndpoints($api_name, array $endpoints_info){

        $api = EntityManager::getRepository(Api::class)->findOneBy(['name' => $api_name]);
        if(is_null($api)) return;

        foreach($endpoints_info as $endpoint_info){

            $endpoint = new ApiEndpoint();
            $endpoint->setName($endpoint_info['name']);
            $endpoint->setRoute($endpoint_info['route']);
            $endpoint->setHttpMethod($endpoint_info['http_method']);
            $endpoint->setStatus(true);
            $endpoint->setAllowCors(true);
            $endpoint->setAllowCredentials(true);
            $endpoint->setApi($api);

            foreach($endpoint_info['scopes'] as $scope_name){
                $scope = EntityManager::getRepository(ApiScope::class)->findOneBy(['name' => $scope_name]);
                if(is_null($scope)) continue;
                $endpoint->addScope($scope);
            }

            EntityManager::persist($endpoint);
        }

        EntityManager::flush();
    }

    /**
     * @param array $scopes_definitions
     * @param string|null $api_name
     */
    public static function seedScopes(array $scopes_definitions, string $api_name = null){

        $api = null;
        if(!is_null($api_name))
            $api = EntityManager::getRepository(Api::class)->findOneBy(['name' => $api_name]);

        foreach ($scopes_definitions as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            if(isset($scope_info['system']))
                $scope->setSystem($scope_info['system']);

            if(isset($scope_info['default']))
                $scope->setDefault($scope_info['default']);

            if(isset($scope_info['groups']))
                $scope->setAssignedByGroups($scope_info['groups']);

            if(!is_null($api))
                $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    public static function createServerExtension(array $payload){

        $ext = new ServerExtension();
        $ext->setName(trim($payload['name']));
        $ext->setNamespace(trim($payload['namespace']));
        $ext->setActive(boolval($payload['active']));
        $ext->setExtensionClass(trim($payload['extension_class']));
        $ext->setDescription(trim($payload['description']));
        $ext->setViewName(trim($payload['view_name']));

        EntityManager::persist($ext);

        EntityManager::flush();
    }
}