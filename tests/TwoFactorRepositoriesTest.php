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
use Auth\Repositories\IUserRepository;
use Auth\User;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;

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
        // Pull any persisted user; tests don't assert on user fields, only on FK linkage
        $userRepo = App::make(IUserRepository::class);
        $this->user = $userRepo->findOneBy([]);
        if (is_null($this->user)) {
            $this->markTestSkipped('No User exists; database must be seeded.');
        }
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
        $code->setUser($this->user);
        $code->setCodeHash(password_hash('TESTCODE', PASSWORD_BCRYPT));

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
        $this->assertGreaterThanOrEqual(1, $deleted);
    }
}
