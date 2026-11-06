<?php namespace Tests;
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

use App\libs\Auth\Models\UserRecoveryCode;
use App\Services\Auth\IRecoveryCodeService;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Serialization of every path that writes or deletes a user's recovery codes
 * (SDS idp-mfa.md §3.5): they all take a PESSIMISTIC_WRITE lock on the users row
 * first. PHPUnit is single-threaded, so the contention is created with real
 * child processes (tests/support/RecoveryCodeWorker.php) that block on a lock
 * this test holds on a second physical connection, and are released together.
 *
 * @package Tests
 */
final class RecoveryCodeConcurrencyTest extends OpenStackIDBaseTestCase
{
    private const ADMIN_EMAIL = 'sebastian@tipit.net';

    private ?Connection $holder = null;

    protected function tearDown(): void
    {
        if ($this->holder !== null) {
            try {
                if ($this->holder->transactionLevel() > 0) {
                    $this->holder->rollBack();
                }
            } finally {
                DB::purge('recovery_lock_holder');
                $this->holder = null;
            }
        }
        parent::tearDown();
    }

    public function testLockUserRowBlocksAConcurrentConnection(): void
    {
        $admin = $this->admin();
        /** @var IUserRecoveryCodeRepository $repo */
        $repo = App::make(IUserRecoveryCodeRepository::class);

        $primary = EntityManager::getConnection();
        $primary->beginTransaction();
        try {
            $repo->lockUserRow($admin);

            $secondary = $this->secondConnection('recovery_lock_probe');
            $secondary->statement('SET SESSION innodb_lock_wait_timeout = 1');
            $secondary->beginTransaction();
            try {
                $secondary->selectOne('SELECT id FROM users WHERE id = ? FOR UPDATE', [$admin->getId()]);
                $this->fail('a second connection must not lock a users row already held by lockUserRow()');
            } catch (\Illuminate\Database\QueryException $ex) {
                $this->assertStringContainsStringIgnoringCase('lock wait timeout', $ex->getMessage());
            } finally {
                $secondary->rollBack();
                DB::purge('recovery_lock_probe');
            }
        } finally {
            $primary->rollBack();
        }
    }

    public function testLockUserRowRefreshesStaleState(): void
    {
        $admin = $this->admin();
        $service = App::make(IRecoveryCodeService::class);
        $batch = $service->generateRecoveryCodes($admin);
        $this->assertTrue($service->acknowledgeRecoveryCodes($admin, $batch->getBatchId()));
        $this->assertNotNull($admin->getRecoveryCodesAcknowledgedAt());

        // Another connection clears the column behind the managed entity's back.
        $other = $this->secondConnection('recovery_lock_stale');
        $other->update('UPDATE users SET recovery_codes_acknowledged_at = NULL WHERE id = ?', [$admin->getId()]);
        DB::purge('recovery_lock_stale');

        $primary = EntityManager::getConnection();
        $primary->beginTransaction();
        try {
            App::make(IUserRecoveryCodeRepository::class)->lockUserRow($admin);
            $this->assertNull(
                $admin->getRecoveryCodesAcknowledgedAt(),
                'the lock must re-hydrate the entity, otherwise acknowledge would decide on stale state'
            );
        } finally {
            $primary->rollBack();
        }
    }

    public function testTwoConcurrentGenerationsLeaveExactlyOneBatch(): void
    {
        $admin = $this->admin();
        $this->holdUsersRowLock($admin);

        $workers = [
            $this->startWorker('generate', $admin->getId()),
            $this->startWorker('generate', $admin->getId()),
        ];
        // Let both workers reach (and block on) the users-row lock.
        sleep(3);
        $this->holder->commit();

        $results = array_map(fn($w) => $this->finishWorker($w), $workers);

        $this->assertTrue($results[0]['ok'], json_encode($results[0]));
        $this->assertTrue($results[1]['ok'], json_encode($results[1]));
        $this->assertNotSame($results[0]['batch_id'], $results[1]['batch_id']);

        $rows = $this->codeRows($admin->getId());
        $this->assertCount(
            (int)config('auth.recovery_codes.count', 10),
            $rows,
            'two concurrent generations must not leave two live batches'
        );
        $this->assertSame(
            max($results[0]['batch_id'], $results[1]['batch_id']),
            min(array_column($rows, 'id')),
            'the surviving batch is the one generated last'
        );
    }

    public function testRedemptionOfACodeReplacedDuringRegenerationIsRejected(): void
    {
        $admin = $this->admin();
        $plain = 'OLDBATCHCODE';
        $this->insertCode($admin->getId(), $plain);

        // The "regeneration" holds the users-row lock; the redemption starts meanwhile
        // and must wait for it, then read the codes AFTER the replacement.
        $this->holdUsersRowLock($admin);
        $worker = $this->startWorker('redeem', $admin->getId(), $plain);
        sleep(3);

        $this->holder->delete('DELETE FROM user_recovery_codes WHERE user_id = ?', [$admin->getId()]);
        $this->holder->insert(
            'INSERT INTO user_recovery_codes (created_at, user_id, code_hash) VALUES (NOW(), ?, ?)',
            [$admin->getId(), Hash::make('NEWBATCHCODE')]
        );
        $this->holder->commit();

        $result = $this->finishWorker($worker);

        $this->assertFalse($result['ok'], 'a code of the replaced batch must not be redeemable');
        $this->assertStringContainsString('Invalid recovery code', $result['error']);
        $this->assertCount(1, $this->codeRows($admin->getId()));
    }

    // ---------------------------------------------------------------------

    private function admin(): User
    {
        $admin = EntityManager::getRepository(User::class)->getByEmailOrName(self::ADMIN_EMAIL);
        $this->assertInstanceOf(User::class, $admin);
        return $admin;
    }

    private function secondConnection(string $name): Connection
    {
        Config::set("database.connections.{$name}", Config::get('database.connections.openstackid'));
        return DB::connection($name);
    }

    /** Takes the users-row lock on a second physical connection, in an open transaction. */
    private function holdUsersRowLock(User $user): void
    {
        $this->holder = $this->secondConnection('recovery_lock_holder');
        $this->holder->beginTransaction();
        $this->holder->selectOne('SELECT id FROM users WHERE id = ? FOR UPDATE', [$user->getId()]);
    }

    private function insertCode(int $userId, string $plain): void
    {
        $conn = $this->secondConnection('recovery_lock_seed');
        $conn->insert(
            'INSERT INTO user_recovery_codes (created_at, user_id, code_hash) VALUES (NOW(), ?, ?)',
            [$userId, Hash::make($plain)]
        );
        DB::purge('recovery_lock_seed');
    }

    /** @return array<int,array{id:int}> */
    private function codeRows(int $userId): array
    {
        $conn = $this->secondConnection('recovery_lock_read');
        $rows = $conn->select('SELECT id FROM user_recovery_codes WHERE user_id = ? ORDER BY id', [$userId]);
        DB::purge('recovery_lock_read');
        return array_map(fn($r) => ['id' => (int)$r->id], $rows);
    }

    /** @return array{proc:resource,pipes:array} */
    private function startWorker(string $action, int $userId, ?string $code = null): array
    {
        $cmd = [PHP_BINARY, base_path('tests/support/RecoveryCodeWorker.php'), $action, (string)$userId];
        if ($code !== null) {
            $cmd[] = $code;
        }
        $proc = proc_open(
            $cmd,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path(),
            array_merge(getenv(), ['APP_ENV' => 'testing', 'XDEBUG_MODE' => 'off'])
        );
        $this->assertIsResource($proc, 'could not start the worker process');
        return ['proc' => $proc, 'pipes' => $pipes];
    }

    private function finishWorker(array $worker): array
    {
        $stdout = stream_get_contents($worker['pipes'][1]);
        $stderr = stream_get_contents($worker['pipes'][2]);
        fclose($worker['pipes'][1]);
        fclose($worker['pipes'][2]);
        proc_close($worker['proc']);

        $lines = array_values(array_filter(array_map('trim', explode(PHP_EOL, $stdout))));
        $decoded = $lines ? json_decode(end($lines), true) : null;
        $this->assertIsArray($decoded, "worker produced no result. stdout: {$stdout} stderr: {$stderr}");
        return $decoded;
    }
}
