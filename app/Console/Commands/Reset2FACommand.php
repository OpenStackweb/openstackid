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

use App\libs\Auth\Models\TwoFactorAuditLog;
use App\Services\Auth\IRecoveryCodeService;
use App\Services\Auth\ITwoFactorAuditService;
use Auth\Repositories\IUserTrustedDeviceRepository;
use Auth\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class Reset2FACommand
 * Resets MFA for one user (lost second factor and recovery codes). It is a
 * privileged operation that only runs with server access: the mandatory
 * --reason and the settings_changed audit row are the accountability trail.
 * @package App\Console\Commands
 */
final class Reset2FACommand extends Command
{
    /**
     * The audit ip_address for events originated from the console.
     */
    private const ConsoleIp = '127.0.0.1';

    /**
     * @var string
     */
    protected $signature = 'idp:reset-2fa {email : Email of the user to reset} {--reason= : Why the reset is needed (required, audited)}';

    /**
     * @var string
     */
    protected $description = 'Reset 2FA for a user: clears the enrollment, deletes recovery codes and revokes trusted devices';

    public function handle
    (
        IRecoveryCodeService $recovery_code_service,
        ITwoFactorAuditService $audit_service,
        IUserTrustedDeviceRepository $trusted_device_repository
    ): int
    {
        $reason = trim((string)$this->option('reason'));
        if ($reason === '') {
            $this->error('The --reason option is required.');
            return self::FAILURE;
        }

        $email = trim((string)$this->argument('email'));
        $user = EntityManager::getRepository(User::class)->findOneBy(['email' => $email]);
        if (is_null($user)) {
            $this->error(sprintf('User %s not found.', $email));
            return self::FAILURE;
        }

        // counted before the reset, they are gone afterwards
        $codes = $recovery_code_service->countUnusedRecoveryCodes($user);
        $devices = count($trusted_device_repository->getActiveByUser($user));

        // the service owns the side effects (flags, codes, devices) in one transaction
        $recovery_code_service->disableTwoFactor($user, null, null);

        $operator = sprintf('%s@%s', get_current_user(), gethostname());

        try {
            $audit_service->log(
                $user,
                TwoFactorAuditLog::EventSettingsChanged,
                $user->getTwoFactorMethod(),
                self::ConsoleIp,
                ['reason' => $reason, 'actor' => 'console', 'operator' => $operator]
            );
        } catch (\Throwable $ex) {
            // the reset is already committed: report it, but do not hide that the trail is missing
            Log::error($ex);
            $this->error(sprintf(
                'The 2FA reset of %s was applied but the settings_changed audit event could not be recorded: %s',
                $email,
                $ex->getMessage()
            ));
            return self::FAILURE;
        }

        $this->info(sprintf('2FA reset for %s (reason: %s, operator: %s)', $email, $reason, $operator));
        $this->line(sprintf('  unused recovery codes deleted: %d', $codes));
        $this->line(sprintf('  trusted devices revoked:       %d', $devices));

        // enforcement is derived from group membership, so a reset cannot lift it
        if ($user->shouldRequire2FA()) {
            $this->warn(
                'This user belongs to a 2FA enforced group: they will still be challenged at the next login. ' .
                'They have no recovery codes now and must regenerate them from the profile Security section ' .
                'after logging in with the email OTP.'
            );
        }

        return self::SUCCESS;
    }
}
