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
 * Class CleanOpenIdStaleData
 * @package Console\Commands
 */
final class CleanOpenIdStaleData extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'idp:openid-clean';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'idp:openid-clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean OpenId stale data';

    const IntervalInSeconds = 86400; // 1 day;
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $interval = self::IntervalInSeconds;

        if (Schema::hasTable('openid_associations')) {
            // delete void associations
            $res = DB::table('openid_associations')
                ->whereRaw("DATE_ADD(issued, INTERVAL lifetime second) <= UTC_TIMESTAMP()")
                ->delete();

            Log::debug(sprintf("CleanOpenIdStaleData::handle %s rows where deleted from openid_associations", $res));
        }

        if (Schema::hasTable('user_exceptions_trail')) {
            // delete old exceptions trails
            $res = DB::table('user_exceptions_trail')
                ->whereRaw("DATE_ADD(created_at, INTERVAL {$interval} second) <= UTC_TIMESTAMP()")
                ->delete();

            Log::debug(sprintf("CleanOpenIdStaleData::handle %s rows where deleted from user_exceptions_trail", $res));
        }

        if (Schema::hasTable('user_actions')) {
            // delete old user actions
            $res = DB::table('user_actions')
                ->whereRaw("DATE_ADD(created_at, INTERVAL 1 year) <= UTC_TIMESTAMP()")
                ->delete();

            Log::debug(sprintf("CleanOpenIdStaleData::handle %s rows where deleted from user_actions", $res));
        }
    }
}