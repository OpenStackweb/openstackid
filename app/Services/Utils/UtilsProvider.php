<?php namespace Services\Utils;
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
use App\Models\Utils\BaseEntity;
use App\Repositories\IServerConfigurationRepository;
use App\Services\Utils\DoctrineTransactionService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Utils\Services\IdentifierGenerator;
use Utils\Services\UniqueIdentifierGenerator;
use Utils\Services\UtilsServiceCatalog;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
/**
 * Class UtilsProvider
 * @package Services\Utils
 */
final class UtilsProvider extends ServiceProvider implements DeferrableProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        App::singleton(IdentifierGenerator::class, UniqueIdentifierGenerator::class);

        App::singleton(UtilsServiceCatalog::CacheService, RedisCacheService::class);
	    App::singleton(UtilsServiceCatalog::TransactionService, function(){
            return new DoctrineTransactionService(BaseEntity::EntityManager);
        });
        App::singleton(UtilsServiceCatalog::LogService, LogService::class);
        App::singleton(UtilsServiceCatalog::LockManagerService,  LockManagerService::class);
        App::singleton(UtilsServiceCatalog::ServerConfigurationService, ServerConfigurationService::class);
        App::singleton(UtilsServiceCatalog::BannedIpService, BannedIPService::class);

        // setting facade
        App::singleton('serverconfigurationservice', function ($app) {
            return new ServerConfigurationService
            (
                App::make(IServerConfigurationRepository::class),
                App::make(UtilsServiceCatalog::CacheService),
                App::make(UtilsServiceCatalog::TransactionService)
            );
        });

        // setting facade
        App::singleton('externalurlservice', function ($app) {
            return new ExternalUrlService();
        });


    }

    public function provides()
    {
        return
            [
                IdentifierGenerator::class,
                UtilsServiceCatalog::CacheService,
                UtilsServiceCatalog::TransactionService,
                UtilsServiceCatalog::LogService,
                UtilsServiceCatalog::LockManagerService,
                UtilsServiceCatalog::ServerConfigurationService,
                UtilsServiceCatalog::BannedIpService,
                ServerConfigurationService::class,
                ExternalUrlService::class,
                'serverconfigurationservice',
                'externalurlservice',
                'ServerConfigurationService',
                'ExternalUrlService',
            ];
    }

    public function when(){
        return array('redis');
    }
}