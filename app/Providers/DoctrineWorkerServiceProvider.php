<?php

namespace App\Providers;

/**
 * Copyright 2026 OpenStack Foundation
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

use App\Console\Commands\DoctrineWorkCommand;
use App\Worker\DoctrineWorker;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class DoctrineWorkerServiceProvider
 * Registers the doctrine:queue:work command and the worker it runs.
 * @package App\Providers
 */
class DoctrineWorkerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            DoctrineWorker::class,
            function (Application $app): DoctrineWorker {
                return new DoctrineWorker(
                    $app['queue'],
                    $app['events'],
                    $app[ExceptionHandler::class],
                    fn(): bool => $app->isDownForMaintenance()
                );
            }
        );

        $this->app->singleton(
            DoctrineWorkCommand::class,
            function (Application $app): DoctrineWorkCommand {
                return new DoctrineWorkCommand($app[DoctrineWorker::class], $app['cache.store']);
            }
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([DoctrineWorkCommand::class]);
        }
    }
}
