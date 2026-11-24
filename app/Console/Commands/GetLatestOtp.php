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

use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use Illuminate\Console\Command;

/**
 * Class GetLatestOtp
 *
 * Prints the value of the latest not-yet-redeemed OTP issued for a given
 * username. Useful for E2E tests that need to complete a real MFA/passwordless
 * challenge (the mailer queues via Redis, so there is no catchable local
 * mailbox to read the code from instead).
 *
 * @package App\Console\Commands
 */
class GetLatestOtp extends Command
{
    protected $signature = 'idp:get-latest-otp {email}';

    protected $description = 'Print the latest not-yet-redeemed OTP value issued for the given username (useful for E2E tests)';

    public function handle(IOAuth2OTPRepository $repository)
    {
        $email = trim($this->argument('email'));

        // DoctrineOAuth2OTPRepository::getByUserNameNotRedeemed() orders by
        // id DESC, so the newest not-yet-redeemed OTP is the FIRST result,
        // not the last - an account with more than one pending OTP (e.g. a
        // prior attempt that was never redeemed) would otherwise return a
        // stale code.
        $otps = $repository->getByUserNameNotRedeemed($email);
        if (empty($otps)) {
            $this->error("no pending otp for {$email}");
            return 1;
        }

        $this->line(reset($otps)->getValue());
        return 0;
    }
}
