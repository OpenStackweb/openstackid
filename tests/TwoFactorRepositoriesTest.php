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
use App\libs\Auth\Models\TwoFactorAuditLog;
use App\libs\Auth\Models\UserRecoveryCode;
use App\libs\Auth\Models\UserTrustedDevice;
use Auth\Repositories\ITwoFactorAuditLogRepository;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\Repositories\IUserTrustedDeviceRepository;
use Auth\User;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\exceptions\ValidationException;

/**
 * @package Tests
 */
class TwoFactorRepositoriesTest extends TestCase
{
    /** @var User */
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('TwoFactor');
        $user->setEmail('test.twofactor.' . uniqid() . '@test.invalid');
        $user->setAddress1('123 Test St');
        $user->setState('CA');
        $user->setCity('Testville');
        $user->setPostCode('00000');
        $user->setCountryIsoCode('US');
        $user->setPic('');
        $user->setLastLoginDate(new \DateTime('now', new \DateTimeZone('UTC')));
        EntityManager::persist($user);
        EntityManager::flush();
        $this->user = $user;
    }

    public function tearDown(): void
    {
        if ($this->user !== null) {
            $managed = EntityManager::find(User::class, $this->user->getId());
            if ($managed !== null) {
                EntityManager::remove($managed);
                EntityManager::flush();
            }
        }
        parent::tearDown();
    }

    public function testTrustedDeviceRoundTrip(): void
    {
        $repo = App::make(IUserTrustedDeviceRepository::class);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $expires = (clone $now)->modify('+30 days');
        $deviceId = hash('sha256', 'test-token-' . uniqid());

        $device = new UserTrustedDevice();
        $device->setUser($this->user);
        $device->setDeviceIdentifier($deviceId);
        $device->setDeviceName('Chrome on MacOS');
        $device->setIpAddress('127.0.0.1');
        $device->setUserAgent('Mozilla/5.0 (test)');
        $device->setTrustedAt($now);
        $device->setExpiresAt($expires);
        $device->setLastSeenAt($now);

        EntityManager::persist($device);
        EntityManager::flush();
        $id = $device->getId();
        $this->assertGreaterThan(0, $id);

        EntityManager::clear();

        $found = $repo->getActiveByUserAndIdentifier($this->user, $deviceId);
        $this->assertNotNull($found);
        $this->assertEquals($deviceId, $found->getDeviceIdentifier());
        $this->assertFalse($found->isRevoked());

        $active = $repo->getActiveByUser($this->user);
        $this->assertNotEmpty($active);

        EntityManager::remove($found);
        EntityManager::flush();
    }

    public function testAuditLogRoundTrip(): void
    {
        $repo = App::make(ITwoFactorAuditLogRepository::class);

        $entry = new TwoFactorAuditLog();
        $entry->setUser($this->user);
        $entry->setEventType(TwoFactorAuditLog::EventChallengeIssued);
        $entry->setMethod(TwoFactorAuditLog::MethodEmailOtp);
        $entry->setIpAddress('10.0.0.1');
        $entry->setUserAgent('Mozilla/5.0 (test)');
        $entry->setMetadata(['challenge_id' => 'abc123']);

        EntityManager::persist($entry);
        EntityManager::flush();
        $id = $entry->getId();
        $this->assertGreaterThan(0, $id);

        EntityManager::clear();

        $recent = $repo->getRecentByUser($this->user, 10);
        $this->assertNotEmpty($recent);
        $found = null;
        foreach ($recent as $row) {
            if ($row->getId() === $id) { $found = $row; break; }
        }
        $this->assertNotNull($found);
        $this->assertEquals(TwoFactorAuditLog::EventChallengeIssued, $found->getEventType());
        $this->assertEquals(['challenge_id' => 'abc123'], $found->getMetadata());

        EntityManager::remove($found);
        EntityManager::flush();
    }

    public function testRecoveryCodeRoundTrip(): void
    {
        $repo = App::make(IUserRecoveryCodeRepository::class);

        $code = new UserRecoveryCode();
        self::setProp($code, 'user', $this->user);
        self::setProp($code, 'code_hash', password_hash('TESTCODE', PASSWORD_BCRYPT));

        EntityManager::persist($code);
        EntityManager::flush();
        $id = $code->getId();
        $this->assertGreaterThan(0, $id);
        $this->assertFalse($code->isUsed());

        EntityManager::clear();

        $unused = $repo->getUnusedByUser($this->user);
        $this->assertNotEmpty($unused);

        $reload = EntityManager::find(UserRecoveryCode::class, $id);
        $reload->markUsed();
        EntityManager::flush();
        $this->assertTrue($reload->isUsed());

        $deleted = $repo->deleteAllForUser($this->user);
        $this->assertEquals(1, $deleted);
    }

    // -------------------------------------------------------------------------
    // Targeted behaviour tests
    // -------------------------------------------------------------------------

    public function testExpiredTrustedDeviceIsExcluded(): void
    {
        $repo     = App::make(IUserTrustedDeviceRepository::class);
        $now      = new \DateTime('now', new \DateTimeZone('UTC'));
        $expired  = (clone $now)->modify('-1 minute');
        $deviceId = hash('sha256', 'expired-device-' . uniqid());

        $device = $this->buildDevice($deviceId, $now, $expired);
        EntityManager::persist($device);
        EntityManager::flush();
        $id = $device->getId();
        EntityManager::clear();

        $this->assertNull(
            $repo->getActiveByUserAndIdentifier($this->user, $deviceId),
            'getActiveByUserAndIdentifier must return null for an expired device.'
        );

        $ids = array_map(
            fn(UserTrustedDevice $d) => $d->getDeviceIdentifier(),
            $repo->getActiveByUser($this->user)
        );
        $this->assertNotContains($deviceId, $ids, 'getActiveByUser must not include expired devices.');

        $stale = EntityManager::find(UserTrustedDevice::class, $id);
        if ($stale) { EntityManager::remove($stale); EntityManager::flush(); }
    }

    public function testRevokedTrustedDeviceIsExcluded(): void
    {
        $repo     = App::make(IUserTrustedDeviceRepository::class);
        $now      = new \DateTime('now', new \DateTimeZone('UTC'));
        $expires  = (clone $now)->modify('+30 days');
        $deviceId = hash('sha256', 'revoked-device-' . uniqid());

        $device = $this->buildDevice($deviceId, $now, $expires);
        $device->setIsRevoked(true);
        EntityManager::persist($device);
        EntityManager::flush();
        $id = $device->getId();
        EntityManager::clear();

        $this->assertNull(
            $repo->getActiveByUserAndIdentifier($this->user, $deviceId),
            'getActiveByUserAndIdentifier must return null for a revoked device.'
        );

        $ids = array_map(
            fn(UserTrustedDevice $d) => $d->getDeviceIdentifier(),
            $repo->getActiveByUser($this->user)
        );
        $this->assertNotContains($deviceId, $ids, 'getActiveByUser must not include revoked devices.');

        $stale = EntityManager::find(UserTrustedDevice::class, $id);
        if ($stale) { EntityManager::remove($stale); EntityManager::flush(); }
    }

    public function testDuplicateDeviceIdentifierCannotOccur(): void
    {
        $connection = EntityManager::getConnection();
        $indexes    = $connection->createSchemaManager()->listTableIndexes('user_trusted_devices');

        $hasUnique = false;
        foreach ($indexes as $index) {
            if ($index->isUnique()) {
                $cols = $index->getColumns();
                if (in_array('user_id', $cols) && in_array('device_identifier', $cols)) {
                    $hasUnique = true;
                    break;
                }
            }
        }

        $this->assertTrue(
            $hasUnique,
            'user_trusted_devices must have a UNIQUE index on (user_id, device_identifier).'
        );
    }

    public function testRecoveryCodeDeletionRemovesUsedAndUnusedCodes(): void
    {
        $repo = App::make(IUserRecoveryCodeRepository::class);

        $unused = new UserRecoveryCode();
        self::setProp($unused, 'user', $this->user);
        self::setProp($unused, 'code_hash', password_hash('UNUSED_' . uniqid(), PASSWORD_BCRYPT));

        $used = new UserRecoveryCode();
        self::setProp($used, 'user', $this->user);
        self::setProp($used, 'code_hash', password_hash('USED_' . uniqid(), PASSWORD_BCRYPT));
        $used->markUsed();

        EntityManager::persist($unused);
        EntityManager::persist($used);
        EntityManager::flush();
        $unusedId = $unused->getId();
        $usedId   = $used->getId();

        $deleted = $repo->deleteAllForUser($this->user);
        $this->assertGreaterThanOrEqual(2, $deleted, 'deleteAllForUser must remove both used and unused codes.');

        EntityManager::clear();
        $this->assertNull(
            EntityManager::find(UserRecoveryCode::class, $unusedId),
            'Unused recovery code must be deleted.'
        );
        $this->assertNull(
            EntityManager::find(UserRecoveryCode::class, $usedId),
            'Used recovery code must also be deleted.'
        );
    }

    public function testAuditLogsReturnedMostRecentFirst(): void
    {
        $repo       = App::make(ITwoFactorAuditLogRepository::class);
        $createdIds = [];

        $timestamps = [
            new \DateTime('2020-01-01 01:00:00', new \DateTimeZone('UTC')),
            new \DateTime('2020-01-01 02:00:00', new \DateTimeZone('UTC')),
            new \DateTime('2020-01-01 03:00:00', new \DateTimeZone('UTC')),
        ];

        $setCreatedAt = static function (TwoFactorAuditLog $log, \DateTime $dt): void {
            $prop = new \ReflectionProperty(TwoFactorAuditLog::class, 'created_at');
            $prop->setAccessible(true);
            $prop->setValue($log, $dt);
        };

        foreach ($timestamps as $ts) {
            $entry = new TwoFactorAuditLog();
            $entry->setUser($this->user);
            $entry->setEventType(TwoFactorAuditLog::EventChallengeIssued);
            $entry->setMethod(TwoFactorAuditLog::MethodEmailOtp);
            $entry->setIpAddress('127.0.0.1');
            $entry->setUserAgent('Mozilla/5.0 (test)');
            $setCreatedAt($entry, $ts);
            EntityManager::persist($entry);
            EntityManager::flush();
            $createdIds[] = $entry->getId();
        }

        EntityManager::clear();

        $all  = $repo->getRecentByUser($this->user, 200);
        $ours = array_values(array_filter($all, fn(TwoFactorAuditLog $e) => in_array($e->getId(), $createdIds)));

        $this->assertCount(3, $ours, 'All three seeded audit entries must be returned.');

        for ($i = 0; $i < count($ours) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $ours[$i + 1]->getCreatedAt()->getTimestamp(),
                $ours[$i]->getCreatedAt()->getTimestamp(),
                'Audit logs must be ordered most-recent first.'
            );
        }

        // cleanup
        foreach ($createdIds as $logId) {
            $log = EntityManager::find(TwoFactorAuditLog::class, $logId);
            if ($log) { EntityManager::remove($log); }
        }
        EntityManager::flush();
    }

    public function testGetUnusedByUserExcludesUsedCodes(): void
    {
        $repo = App::make(IUserRecoveryCodeRepository::class);

        $unused = new UserRecoveryCode();
        self::setProp($unused, 'user', $this->user);
        self::setProp($unused, 'code_hash', password_hash('UNUSED_' . uniqid(), PASSWORD_BCRYPT));

        $used = new UserRecoveryCode();
        self::setProp($used, 'user', $this->user);
        self::setProp($used, 'code_hash', password_hash('USED_' . uniqid(), PASSWORD_BCRYPT));
        $used->markUsed();

        EntityManager::persist($unused);
        EntityManager::persist($used);
        EntityManager::flush();
        $unusedId = $unused->getId();
        $usedId   = $used->getId();

        EntityManager::clear();

        $result = $repo->getUnusedByUser($this->user);
        $ids    = array_map(fn(UserRecoveryCode $c) => $c->getId(), $result);

        $this->assertContains($unusedId, $ids, 'getUnusedByUser must include the unused code.');
        $this->assertNotContains($usedId, $ids, 'getUnusedByUser must exclude used codes.');

        foreach ([$unusedId, $usedId] as $codeId) {
            $code = EntityManager::find(UserRecoveryCode::class, $codeId);
            if ($code) { EntityManager::remove($code); }
        }
        EntityManager::flush();
    }

    public function testMarkUsedTwiceThrows(): void
    {
        $code = new UserRecoveryCode();
        self::setProp($code, 'user', $this->user);
        self::setProp($code, 'code_hash', password_hash('TEST_' . uniqid(), PASSWORD_BCRYPT));
        $code->markUsed();

        $this->expectException(ValidationException::class);
        $code->markUsed();
    }

    public function testSetCodeHashRejectsPlaintext(): void
    {
        $this->markTestSkipped('setCodeHash() was removed from UserRecoveryCode; plaintext validation no longer has an entry point.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function setProp(object $obj, string $prop, mixed $value): void
    {
        $p = new \ReflectionProperty($obj, $prop);
        $p->setAccessible(true);
        $p->setValue($obj, $value);
    }

    private function buildDevice(string $deviceId, \DateTime $now, \DateTime $expires): UserTrustedDevice
    {
        $device = new UserTrustedDevice();
        $device->setUser($this->user);
        $device->setDeviceIdentifier($deviceId);
        $device->setDeviceName('Test Browser');
        $device->setIpAddress('127.0.0.1');
        $device->setUserAgent('Mozilla/5.0 (test)');
        $device->setTrustedAt($now);
        $device->setExpiresAt($expires);
        $device->setLastSeenAt($now);
        return $device;
    }
}
