<?php namespace App\Console\Commands;
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
use App\Services\Auth\IFacebookDataDeletionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class FacebookDataDeletionBackfill
 * Processes the "Download User Identifiers" CSV Facebook lets admins export
 * from the app dashboard's Advanced Settings, unlinking each app-scoped ID
 * from its matching OpenStackID user (same logic as the live callback).
 * @package App\Console\Commands
 */
final class FacebookDataDeletionBackfill extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'idp:facebook-data-deletion-backfill';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'idp:facebook-data-deletion-backfill {path : Absolute path to the Facebook user identifiers CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a Facebook "Download User Identifiers" CSV export, unlinking each app-scoped ID from its OpenStackID user.';

    /**
     * @var IFacebookDataDeletionService
     */
    private $service;

    /**
     * FacebookDataDeletionBackfill constructor.
     * @param IFacebookDataDeletionService $service
     */
    public function __construct(IFacebookDataDeletionService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * @return int
     */
    public function handle()
    {
        $path = $this->argument('path');

        if (!is_readable($path)) {
            $this->error(sprintf("File %s is not readable.", $path));
            return 1;
        }

        $matched = 0;
        $not_found = 0;
        $skipped = 0;

        $handle = fopen($path, 'r');
        while (($line = fgets($handle)) !== false) {
            $external_id = trim($line, " \t\n\r\0\x0B\"'");

            if ($external_id === '') {
                $skipped++;
                continue;
            }

            $result = $this->service->processDeletionRequest($external_id);

            if ($result['status'] === 'completed') {
                $matched++;
            } else {
                $not_found++;
            }

            Log::debug(sprintf("FacebookDataDeletionBackfill::handle processed request with status %s", $result['status']));
        }
        fclose($handle);

        $this->info(sprintf("Processed CSV: %d matched, %d not found, %d skipped blank lines.", $matched, $not_found, $skipped));
        return 0;
    }
}
