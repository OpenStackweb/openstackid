<?php

namespace App\Console\Commands;

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

use App\Worker\DoctrineWorker;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Console\WorkCommand as IlluminateWorkCommand;

/**
 * Class DoctrineWorkCommand
 * Same options as queue:work, run by a worker that is safe to use with Doctrine.
 * @package App\Console\Commands
 */
class DoctrineWorkCommand extends IlluminateWorkCommand
{
    protected $description = 'Works jobs from the queue in a way that is compatible with Doctrine.';

    public function __construct(DoctrineWorker $worker, Cache $cache)
    {
        // keep the signature of the built-in queue worker, under our own command name
        $this->signature = str_replace('queue:work', 'doctrine:queue:work', $this->signature);
        parent::__construct($worker, $cache);
    }
}
