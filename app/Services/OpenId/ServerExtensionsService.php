<?php namespace Services\OpenId;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\Repositories\IServerExtensionRepository;
use App\Services\AbstractService;
use OpenId\Services\IServerExtensionsService;
use Utils\Db\ITransactionService;
use Utils\Services\ServiceLocator;
use ReflectionClass;
/**
 * Class ServerExtensionsService
 * @package Services\OpenId
 */
final class ServerExtensionsService extends AbstractService implements IServerExtensionsService
{
    /**
     * @var IServerExtensionRepository
     */
    private $server_extensions_repository;

    public function __construct(
        IServerExtensionRepository $server_extensions_repository,
        ITransactionService $tx_service)
    {
        parent::__construct($tx_service);
        $this->server_extensions_repository = $server_extensions_repository;
    }

    /**
	 * @return array
	 */
	public function getAllActiveExtensions()
    {
        return $this->tx_service->transaction(function(){
            $extensions = $this->server_extensions_repository->getAllActives();
            $res        = [];
            foreach ($extensions as $extension) {
                $class_name = $extension->getExtensionClass();
                if (empty($class_name)) continue;

                $class              = new ReflectionClass($class_name);
                $constructor        = $class->getConstructor();
                $constructor_params = $constructor->getParameters();

                $deps = [];

                foreach($constructor_params as $constructor_param){
                    $param_class  = $constructor_param->getClass();
                    if(is_null($param_class)){
                        $property = $constructor_param->getName();
                        $deps[] = $extension->{$property};
                        continue;
                    }
                    $deps [] = ServiceLocator::getInstance()->getService($param_class->getName());
                }

                $res[] = $class->newInstanceArgs($deps);
            }
            return $res;
        });

    }
}