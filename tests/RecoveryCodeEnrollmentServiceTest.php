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

use App\libs\Auth\Factories\UserFactory;
use App\libs\Auth\Models\UserRecoveryCode;
use App\Services\Auth\IRecoveryCodeService;
use Auth\AuthHelper;
use Auth\MFAConstants;
use Auth\User;
use Database\Migrations\Version20261105120000;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Psr\Log\NullLogger;

/**
 * Enrollment condition, window and acknowledgment rules of the recovery-code
 * service (SDS idp-mfa.md §3.5, §4.10.3), plus the migration backfill (§3.6).
 *
 * @package Tests
 */
final class RecoveryCodeEnrollmentServiceTest extends OpenStackIDBaseTestCase
{
    private IRecoveryCodeService $service;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        Session::start();
        $this->service = App::make(IRecoveryCodeService::class);
    }

    // ------------------------------------------------------------------
    // requiresRecoveryCodeEnrollment
    // ------------------------------------------------------------------

    public function testEnforcedUserWithNoCodesRequiresEnrollment(): void
    {
        $this->assertTrue($this->service->requiresRecoveryCodeEnrollment($this->admin()));
    }

    public function testEnforcedUserWithAnUnacknowledgedBatchRequiresEnrollment(): void
    {
        $admin = $this->admin();
        $this->service->generateRecoveryCodes($admin);

        $this->assertTrue($this->service->requiresRecoveryCodeEnrollment($admin));
    }

    public function testEnforcedUserWithAnAcknowledgedBatchDoesNotRequireEnrollment(): void
    {
        $admin = $this->admin();
        $batch = $this->service->generateRecoveryCodes($admin);
        $this->assertTrue($this->service->acknowledgeRecoveryCodes($admin, $batch->getBatchId()));

        $this->assertFalse($this->service->requiresRecoveryCodeEnrollment($admin));
    }

    public function testAcknowledgedBatchThatWasFullyConsumedRequiresEnrollmentAgain(): void
    {
        $admin = $this->admin();
        $batch = $this->service->generateRecoveryCodes($admin);
        $this->service->acknowledgeRecoveryCodes($admin, $batch->getBatchId());
        DB::table('user_recovery_codes')->where('user_id', $admin->getId())->update(['used_at' => now()]);
        EntityManager::clear();

        $this->assertTrue($this->service->requiresRecoveryCodeEnrollment($this->admin()));
    }

    public function testPlainUserWithoutTwoFactorNeverRequiresEnrollment(): void
    {
        $this->assertFalse($this->service->requiresRecoveryCodeEnrollment($this->plainUser()));
    }

    public function testSelfEnrolledUserWithNoCodesRequiresEnrollment(): void
    {
        $user = $this->plainUser();
        $user->setTwoFactorEnabled(true);

        $this->assertTrue($this->service->requiresRecoveryCodeEnrollment($user));
    }

    public function testKillSwitchTurnsEnrollmentOff(): void
    {
        config(['two_factor.enabled' => false]);

        $this->assertFalse($this->service->requiresRecoveryCodeEnrollment($this->admin()));
    }

    // ------------------------------------------------------------------
    // isWithinEnrollmentWindow
    // ------------------------------------------------------------------

    public function testWindowIsClosedWithoutMarker(): void
    {
        $this->assertFalse($this->service->isWithinEnrollmentWindow($this->admin()));
    }

    public function testWindowIsOpenRightAfterAChallenge(): void
    {
        $admin = $this->admin();
        $this->putMarker($admin->getId(), time());

        $this->assertTrue($this->service->isWithinEnrollmentWindow($admin));
    }

    public function testWindowClosesAfterTheConfiguredSeconds(): void
    {
        $admin = $this->admin();
        config(['two_factor.recovery_enrollment_window' => 120]);

        $this->putMarker($admin->getId(), time() - 100);
        $this->assertTrue($this->service->isWithinEnrollmentWindow($admin));

        $this->putMarker($admin->getId(), time() - 130);
        $this->assertFalse($this->service->isWithinEnrollmentWindow($admin));
    }

    public function testWindowIsBoundToTheMarkersUser(): void
    {
        $admin = $this->admin();
        $this->putMarker($admin->getId() + 1000, time());

        $this->assertFalse($this->service->isWithinEnrollmentWindow($admin));
    }

    public function testWindowRejectsAMalformedOrFutureMarker(): void
    {
        $admin = $this->admin();

        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, 'garbage');
        $this->assertFalse($this->service->isWithinEnrollmentWindow($admin));

        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, ['user_id' => $admin->getId()]);
        $this->assertFalse($this->service->isWithinEnrollmentWindow($admin));

        $this->putMarker($admin->getId(), time() + 3600);
        $this->assertFalse($this->service->isWithinEnrollmentWindow($admin), 'a marker from the future must not open the window');
    }

    // ------------------------------------------------------------------
    // acknowledgment / batch id / status
    // ------------------------------------------------------------------

    public function testAcknowledgeWithoutAnyBatchIsRejected(): void
    {
        $this->assertFalse($this->service->acknowledgeRecoveryCodes($this->admin(), 1));
    }

    public function testGenerationReturnsTheMinIdOfTheBatchAndClearsAcknowledgment(): void
    {
        $admin = $this->admin();
        $first = $this->service->generateRecoveryCodes($admin);
        $this->service->acknowledgeRecoveryCodes($admin, $first->getBatchId());

        $second = $this->service->generateRecoveryCodes($admin);

        $this->assertCount((int)config('auth.recovery_codes.count', 10), $second->getCodes());
        $this->assertSame($second->getBatchId(), $this->service->getCurrentBatchId($admin));
        $this->assertSame(
            $second->getBatchId(),
            (int)DB::table('user_recovery_codes')->where('user_id', $admin->getId())->min('id')
        );
        $this->assertNull($admin->getRecoveryCodesAcknowledgedAt());
        $this->assertFalse($this->service->acknowledgeRecoveryCodes($admin, $first->getBatchId()), 'the replaced batch can no longer be acknowledged');
    }

    public function testStatusExposesAcknowledgmentAndEnrollmentKeys(): void
    {
        $admin = $this->admin();

        $before = $this->service->getStatus($admin)->toArray();
        $this->assertFalse($before['recovery_codes_acknowledged']);
        $this->assertTrue($before['recovery_codes_enrollment_required']);
        $this->assertSame(0, $before['recovery_codes_remaining']);

        $batch = $this->service->generateRecoveryCodes($admin);
        $this->service->acknowledgeRecoveryCodes($admin, $batch->getBatchId());

        $after = $this->service->getStatus($admin)->toArray();
        $this->assertTrue($after['recovery_codes_acknowledged']);
        $this->assertFalse($after['recovery_codes_enrollment_required']);
        $this->assertSame((int)config('auth.recovery_codes.count', 10), $after['recovery_codes_remaining']);
    }

    // ------------------------------------------------------------------
    // migration backfill (SDS §3.6)
    // ------------------------------------------------------------------

    public function testBackfillAcknowledgesUsersWithUnusedCodesOnly(): void
    {
        $ids = DB::table('users')->orderBy('id')->limit(4)->pluck('id')->all();
        [$withUnused, $onlyUsed, $none, $alreadyAck] = $ids;

        DB::table('users')->whereIn('id', $ids)->update(['recovery_codes_acknowledged_at' => null]);

        // withUnused: used + unused rows, latest created_at wins.
        $this->insertCodeRow($withUnused, '2026-01-01 10:00:00', true);
        $this->insertCodeRow($withUnused, '2026-02-02 11:30:00', false);
        $this->insertCodeRow($withUnused, '2026-02-02 11:00:00', false);
        // onlyUsed: nothing usable left, stays NULL.
        $this->insertCodeRow($onlyUsed, '2026-03-03 09:00:00', true);
        // alreadyAck: an existing value must not be overwritten.
        $this->insertCodeRow($alreadyAck, '2026-04-04 09:00:00', false);
        DB::table('users')->where('id', $alreadyAck)->update(['recovery_codes_acknowledged_at' => '2026-05-05 05:05:05']);

        $connection = EntityManager::getConnection();
        $migration = new Version20261105120000($connection, new NullLogger());
        $migration->postUp($connection->createSchemaManager()->introspectSchema());

        $ack = fn(int $id) => DB::table('users')->where('id', $id)->value('recovery_codes_acknowledged_at');
        $this->assertSame('2026-02-02 11:30:00', $ack($withUnused), 'MAX(created_at) of the user rows');
        $this->assertNull($ack($onlyUsed), 'only used codes: stays NULL');
        $this->assertNull($ack($none), 'no codes: stays NULL');
        $this->assertSame('2026-05-05 05:05:05', $ack($alreadyAck), 're-running must not overwrite an acknowledgment');
    }

    // ------------------------------------------------------------------

    private function admin(): User
    {
        $admin = EntityManager::getRepository(User::class)->getByEmailOrName('sebastian@tipit.net');
        $this->assertInstanceOf(User::class, $admin);
        return $admin;
    }

    private function plainUser(): User
    {
        $user = UserFactory::build([
            'first_name'     => 'Plain',
            'last_name'      => 'User',
            'email'          => 'plain.' . uniqid() . '@test.invalid',
            'password'       => '1Qaz2wsx!',
            'password_enc'   => AuthHelper::AlgSHA1_V2_4,
            'active'         => true,
            'email_verified' => true,
            'identifier'     => 'plain.' . uniqid(),
        ]);
        EntityManager::persist($user);
        EntityManager::flush();
        return $user;
    }

    private function putMarker(int $userId, int $at): void
    {
        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, ['user_id' => $userId, 'at' => $at]);
    }

    private function insertCodeRow(int $userId, string $createdAt, bool $used): void
    {
        DB::table('user_recovery_codes')->insert([
            'user_id'    => $userId,
            'code_hash'  => Hash::make(uniqid('', true)),
            'created_at' => $createdAt,
            'used_at'    => $used ? $createdAt : null,
        ]);
    }
}
