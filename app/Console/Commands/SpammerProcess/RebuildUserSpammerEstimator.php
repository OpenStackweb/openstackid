<?php namespace App\Console\Commands\SpammerProcess;
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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Exception;
/**
 * Class RebuildUserSpammerEstimator
 * @package App\Console\Commands\SpammerProcess
 */
final class RebuildUserSpammerEstimator extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user-spam:rebuild';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-spam:rebuild';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild User spam estimator';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $is_enabled = boolval(Config::get("jobs.spam_processing_enabled"));
            if(!$is_enabled){
                return;
            }

            $connections = Config::get('database.connections', []);
            $db = $connections['openstackid'] ?? [];
            $host = $db['host'] ?? '';
            $database = $db['database'] ?? '';
            $username = $db['username'] ?? '';
            $password = $db['password'] ?? '';

            $command = [sprintf('%s/app/Console/Commands/SpammerProcess/estimator_build.sh', base_path()),
                base_path() . '/app/Console/Commands/SpammerProcess',
                $host,
                $username,
                $password,
                $database
            ];

            Log::debug(sprintf("RebuildUserSpammerEstimator::handle running command %s", implode(" ", $command)));

            $process = new Process($command);
            $process->setTimeout(PHP_INT_MAX);
            $process->setIdleTimeout(PHP_INT_MAX);
            $process->run();

            while ($process->isRunning()) {
            }

            $output = $process->getOutput();

            Log::debug(sprintf("RebuildUserSpammerEstimator::handle output %s", $output));

            if (!$process->isSuccessful()) {
                throw new Exception("Process Error!");
            }
        }
        catch (Exception $ex){
            Log::error($ex);
        }
    }
}