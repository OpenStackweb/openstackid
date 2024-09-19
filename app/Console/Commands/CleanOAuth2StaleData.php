<?php namespace App\Console\Commands;
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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class CleanOAuth2StaleData
 * @package Console\Commands
 */
final class CleanOAuth2StaleData extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'idp:oauth2-clean';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'idp:oauth2-clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean OAuth2 stale data';

    const IntervalInSeconds = 86400; // 1 day;
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // delete void access tokens

        if (Schema::hasTable('oauth2_access_token')) {
            $res = DB::table('oauth2_access_token')
                ->whereRaw("DATE_ADD(created_at, INTERVAL lifetime second) <= UTC_TIMESTAMP()")
                ->delete();
            Log::debug(sprintf("CleanOAuth2StaleData::handle %s rows where deleted from oauth2_access_token", $res));
            $this->info(sprintf("CleanOAuth2StaleData::handle %s rows where deleted from oauth2_access_token", $res));
        }

        if (Schema::hasTable('oauth2_otp')) {
            $res = DB::table('oauth2_otp')
                ->whereRaw("lifetime > 0 and DATE_ADD(created_at, INTERVAL lifetime second) <= UTC_TIMESTAMP()")
                ->delete();

            Log::debug(sprintf("CleanOAuth2StaleData::handle %s rows where deleted from oauth2_otp", $res));
            $this->info(sprintf("CleanOAuth2StaleData::handle %s rows where deleted from oauth2_otp", $res));
        }

    }
}