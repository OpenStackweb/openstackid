<?php namespace App\Console;
/**
 * Copyright 2017 OpenStack Foundation
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
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
/**
 * Class Kernel
 * @package App\Console
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        Commands\CleanOAuth2StaleData::class,
        Commands\CleanOpenIdStaleData::class,
        Commands\CreateSuperAdmin::class,
        Commands\SpammerProcess\RebuildUserSpammerEstimator::class,
        Commands\SpammerProcess\UserSpammerProcessor::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('idp:oauth2-clean')->dailyAt("02:30")->withoutOverlapping()->onOneServer();
        $schedule->command('idp:openid-clean')->dailyAt("03:30")->withoutOverlapping()->onOneServer();
        // user spammer
        $schedule->command('user-spam:rebuild')->dailyAt("02:30")->withoutOverlapping()->onOneServer();
        $schedule->command('user-spam:process')->dailyAt("03:30")->withoutOverlapping()->onOneServer();
    }
}
