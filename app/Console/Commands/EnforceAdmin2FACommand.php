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
use App\Mail\TwoFactorEnforcedMail;
use App\Services\Auth\ITwoFactorAuditService;
use Auth\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class EnforceAdmin2FACommand
 * Onboards every user of the groups in config('two_factor.enforced_groups'):
 * notification email + audit row (the audit row is the "already notified" marker). It deliberately does NOT touch
 * two_factor_enabled / two_factor_method / two_factor_enforced_at: enforcement
 * is derived from group membership at runtime (User::shouldRequire2FA()).
 * @package App\Console\Commands
 */
final class EnforceAdmin2FACommand extends Command
{
    private const ChunkSize = 100;

    /**
     * The audit ip_address for events originated from the console.
     */
    private const ConsoleIp = '127.0.0.1';

    private const AuditSource = 'enforce-admin-2fa';

    /**
     * @var string
     */
    protected $signature = 'idp:enforce-admin-2fa {--dry-run : List what would be done without writing anything}';

    /**
     * @var string
     */
    protected $description = 'Notify every user of the 2FA enforced groups that 2FA is now required';

    public function handle(ITwoFactorAuditService $audit_service): int
    {
        $dry_run = (bool)$this->option('dry-run');
        $slugs = array_values(array_filter((array)config('two_factor.enforced_groups', [])));

        if (empty($slugs)) {
            $this->warn('No groups configured in two_factor.enforced_groups, nothing to do.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%sEnforced groups: %s',
            $dry_run ? '[DRY RUN] ' : '',
            implode(', ', $slugs)
        ));

        $processed = 0;
        $skipped = 0;
        $errors = 0;
        $last_id = 0;

        do {
            // keyset pagination over ids: the loop never holds the whole user set in memory
            $ids = $this->getUserIdsChunk($slugs, $last_id);

            foreach ($ids as $id) {
                $last_id = $id;
                $email = (string)$id;
                try {
                    $user = EntityManager::getRepository(User::class)->find($id);
                    if (is_null($user)) {
                        continue;
                    }
                    $email = $user->getEmail();

                    // the audit row is written only after the mail was sent, so a failed
                    // run leaves no marker and the next run retries exactly those users
                    if ($this->wasAlreadyNotified($user)) {
                        $skipped++;
                        $this->line(sprintf('  skipped   %s (already notified)', $email));
                        continue;
                    }

                    if ($dry_run) {
                        $processed++;
                        $this->line(sprintf('  would process %s', $email));
                        continue;
                    }

                    // at-least-once: if the audit write fails after the mail was sent,
                    // the user is notified again on the next run
                    Mail::send(new TwoFactorEnforcedMail($user));

                    $audit_service->log(
                        $user,
                        TwoFactorAuditLog::EventEnrollmentChanged,
                        TwoFactorAuditLog::MethodEmailOtp,
                        self::ConsoleIp,
                        ['source' => self::AuditSource]
                    );

                    $processed++;
                    $this->line(sprintf('  processed %s', $email));
                } catch (\Throwable $ex) {
                    $errors++;
                    Log::error($ex);
                    $this->error(sprintf('  error     %s: %s', $email, $ex->getMessage()));
                }
            }
        } while (count($ids) === self::ChunkSize);

        $this->info(sprintf(
            '%sSummary: processed %d, skipped %d, errors %d',
            $dry_run ? '[DRY RUN] ' : '',
            $processed,
            $skipped,
            $errors
        ));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function wasAlreadyNotified(User $user): bool
    {
        $logs = EntityManager::getRepository(TwoFactorAuditLog::class)->findBy([
            'user' => $user,
            'event_type' => TwoFactorAuditLog::EventEnrollmentChanged,
        ]);
        foreach ($logs as $log) {
            if (($log->getMetadata()['source'] ?? null) === self::AuditSource) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string[] $slugs
     * @return int[]
     */
    private function getUserIdsChunk(array $slugs, int $after_id): array
    {
        $rows = EntityManager::createQuery(
            'SELECT DISTINCT u.id FROM ' . User::class . ' u JOIN u.groups g ' .
            'WHERE g.slug IN (:slugs) AND u.id > :after_id ORDER BY u.id ASC'
        )
            ->setParameter('slugs', $slugs)
            ->setParameter('after_id', $after_id)
            ->setMaxResults(self::ChunkSize)
            ->getScalarResult();

        return array_map(static fn(array $row) => (int)$row['id'], $rows);
    }
}
