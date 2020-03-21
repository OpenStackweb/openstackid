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
use App\libs\Utils\CSVReader;
use App\Mail\UserSpammerProcessorResultsEmail;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Process\Process;
use Exception;
/**
 * Class UserSpammerProcessor
 * @package App\Console\Commands\SpammerProcess
 */
final class UserSpammerProcessor  extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user-spam:process';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-spam:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process User spam estimator';

    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * MemberSpammerProcessor constructor.
     * @param IUserRepository $user_repository
     */
    public function __construct(IUserRepository $user_repository)
    {
        parent::__construct();
        $this->user_repository = $user_repository;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        $connections = Config::get('database.connections', []);
        $db          = $connections['openstackid'] ?? [];
        $host        = $db['host'] ?? '';
        $database    = $db['database'] ?? '';
        $username    = $db['username'] ?? '';
        $password    = $db['password'] ?? '';

        $command = sprintf(
            '%s/app/Console/Commands/SpammerProcess/estimator_process.sh "%s" "%s" "%s" "%s" "%s"',
            base_path(),
            base_path().'/app/Console/Commands/SpammerProcess',
            $host,
            $username,
            $password,
            $database
        );
        $default = Config::get("database.default");
        $process = new Process($command);
        $process->setTimeout(PHP_INT_MAX);
        $process->setIdleTimeout(PHP_INT_MAX);
        $process->run();

        while ($process->isRunning()) {
        }

        $csv_content = $process->getOutput();

        if (!$process->isSuccessful()) {
            throw new Exception("Process Error!");
        }

        $rows = CSVReader::load($csv_content);

        // send email with excerpt

        $users = [];

        foreach($rows as $row) {
            $user_id = intval($row["ID"]);
            $type    = $row["Type"];
            $user    = $this->user_repository->getById($user_id);
            if(is_null($user) || !$user instanceof User) continue;

            $users[] = [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'full_name' => $user->getFullName(),
                'spam_type' => $type,
                'edit_link' => URL::route("edit_user", ["user_id" => $user->getId()], true)
            ];
        }

        if(count($users) > 0){
            Mail::queue(new UserSpammerProcessorResultsEmail($users));
        }
    }
}