<?php
namespace Tests;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\libs\Auth\Models\UserTrustedDevice;
use App\Services\Auth\DeviceTrustService;
use App\Services\Auth\ITwoFactorAuditService;
use Auth\Repositories\IUserTrustedDeviceRepository;
use Auth\User;
use DateTime;
use DateInterval;
use DateTimeZone;
use Mockery;
use Utils\Db\ITransactionService;

/**
 * Class DeviceTrustServiceTest
 * @package Tests
 */
final class DeviceTrustServiceTest extends BrowserKitTestCase
{
    private DeviceTrustService $service;

    /** @var \Mockery\MockInterface&IUserTrustedDeviceRepository */
    private $repo;

    /** @var \Mockery\MockInterface&ITwoFactorAuditService */
    private $audit_service;

    /** @var \Mockery\MockInterface&ITransactionService */
    private $tx_service;

    public function setUp(): void
    {
        parent::setUp();
        $this->repo = Mockery::mock(IUserTrustedDeviceRepository::class);
        $this->audit_service = Mockery::mock(ITwoFactorAuditService::class);
        $this->audit_service->shouldReceive('log')->byDefault();
        $this->tx_service = Mockery::mock(ITransactionService::class);
        $this->tx_service->shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb())->byDefault();
        $this->service = new DeviceTrustService($this->repo, $this->audit_service, $this->tx_service);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    // -------------------------------------------------------------------------
    // isDeviceTrusted
    // -------------------------------------------------------------------------

    public function testIsDeviceTrustedNullCookie(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);
        $this->repo->shouldNotReceive('getByUserAndDeviceIdentifier');

        $this->assertFalse($this->service->isDeviceTrusted($user, null));
    }

    public function testIsDeviceTrustedEmptyCookie(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);
        $this->repo->shouldNotReceive('getByUserAndDeviceIdentifier');

        $this->assertFalse($this->service->isDeviceTrusted($user, ''));
    }

    public function testIsDeviceTrustedWrongCookie(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);
        $this->repo
            ->shouldReceive('getByUserAndDeviceIdentifier')
            ->once()
            ->andReturn(null);

        $this->assertFalse($this->service->isDeviceTrusted($user, 'unknowntoken'));
    }

    public function testIsDeviceTrustedRevokedDevice(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        $device = $this->makeDevice(expired: false, revoked: true);

        $this->repo
            ->shouldReceive('getByUserAndDeviceIdentifier')
            ->once()
            ->andReturn($device);

        $this->assertFalse($this->service->isDeviceTrusted($user, 'sometoken'));
    }

    public function testIsDeviceTrustedExpiredDevice(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        $device = $this->makeDevice(expired: true, revoked: false);

        $this->repo
            ->shouldReceive('getByUserAndDeviceIdentifier')
            ->once()
            ->andReturn($device);

        $this->assertFalse($this->service->isDeviceTrusted($user, 'sometoken'));
    }

    public function testIsDeviceTrustedValidDevice(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        $device = $this->makeDevice(expired: false, revoked: false);

        $this->repo
            ->shouldReceive('getByUserAndDeviceIdentifier')
            ->once()
            ->andReturn($device);
        $this->repo->shouldReceive('add')->once();

        $this->assertTrue($this->service->isDeviceTrusted($user, 'sometoken'));
    }

    public function testIsDeviceTrustedUpdatesLastSeenAt(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        $device = $this->makeDevice(expired: false, revoked: false);
        // set last_seen_at to a known old value so the update is detectable
        $oldDate = new DateTime('2000-01-01', new DateTimeZone('UTC'));
        $device->setLastSeenAt($oldDate);

        $this->repo
            ->shouldReceive('getByUserAndDeviceIdentifier')
            ->once()
            ->andReturn($device);
        $this->repo->shouldReceive('add')->once();

        $this->service->isDeviceTrusted($user, 'sometoken');

        $this->assertNotNull($device);
        $this->assertGreaterThan($oldDate, $device->getLastSeenAt());
    }

    // -------------------------------------------------------------------------
    // trustDevice
    // -------------------------------------------------------------------------

    public function testTrustDeviceReturnsToken(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        $this->repo->shouldReceive('add')->once();

        $token = $this->service->trustDevice($user, 'Mozilla/5.0', '127.0.0.1');

        $this->assertSame(128, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $token);
    }

    public function testTrustDeviceStoresHash(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        /** @var UserTrustedDevice|null $persistedDevice */
        $persistedDevice = null;

        $this->repo
            ->shouldReceive('add')
            ->once()
            ->withArgs(function ($device) use (&$persistedDevice) {
                $persistedDevice = $device;
                return true;
            });

        $rawToken = $this->service->trustDevice($user, 'Mozilla/5.0', '127.0.0.1');

        $this->assertNotNull($persistedDevice);
        $this->assertSame(hash('sha256', $rawToken), $persistedDevice->getDeviceIdentifier());
    }

    public function testTrustDeviceRawTokenNotStored(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        /** @var UserTrustedDevice|null $persistedDevice */
        $persistedDevice = null;

        $this->repo
            ->shouldReceive('add')
            ->once()
            ->withArgs(function ($device) use (&$persistedDevice) {
                $persistedDevice = $device;
                return true;
            });

        $rawToken = $this->service->trustDevice($user, 'Mozilla/5.0', '127.0.0.1');

        $this->assertNotNull($persistedDevice);
        $this->assertNotSame($rawToken, $persistedDevice->getDeviceIdentifier());
    }

    public function testTrustDeviceCreatesExactlyOneRecord(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        $this->repo->shouldReceive('add')->once();

        $this->service->trustDevice($user, 'Mozilla/5.0', '127.0.0.1');
    }

    public function testTrustDeviceEmitsDeviceTrustedAuditEvent(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        $this->repo->shouldReceive('add')->once();

        $this->audit_service
            ->shouldReceive('log')
            ->once()
            ->with($user, \App\libs\Auth\Models\TwoFactorAuditLog::EventDeviceTrusted, User::MFAMethod_OTP, '127.0.0.1');

        $this->service->trustDevice($user, 'Mozilla/5.0', '127.0.0.1');
    }

    public function testTrustDeviceSetsExpiresAtFromConfig(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        /** @var UserTrustedDevice|null $persistedDevice */
        $persistedDevice = null;

        $this->repo
            ->shouldReceive('add')
            ->once()
            ->withArgs(function ($device) use (&$persistedDevice) {
                $persistedDevice = $device;
                return true;
            });

        $this->service->trustDevice($user, 'Mozilla/5.0', '127.0.0.1');

        $this->assertNotNull($persistedDevice);

        $lifetimeDays = (int) config('two_factor.device_trust_lifetime_days', 30);
        $diff = $persistedDevice->getTrustedAt()->diff($persistedDevice->getExpiresAt());
        $this->assertSame($lifetimeDays, $diff->days);
    }

    // -------------------------------------------------------------------------
    // removeTrustedDevices
    // -------------------------------------------------------------------------

    public function testRemoveTrustedDevicesRevokesAll(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);

        $this->repo
            ->shouldReceive('revokeAllForUser')
            ->once()
            ->with($user);

        $this->audit_service
            ->shouldReceive('log')
            ->once()
            ->with($user, \App\libs\Auth\Models\TwoFactorAuditLog::EventDeviceRevoked, User::MFAMethod_OTP, Mockery::type('string'));

        $this->service->removeTrustedDevices($user);
    }

    // -------------------------------------------------------------------------
    // generateDeviceIdentifier
    // -------------------------------------------------------------------------

    public function testGenerateDeviceIdentifierReturnsSha256(): void
    {
        $token = 'test_token_value';
        $expected = hash('sha256', $token);

        $this->assertSame($expected, $this->service->generateDeviceIdentifier($token));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeDevice(bool $expired, bool $revoked): UserTrustedDevice
    {
        $device = new UserTrustedDevice();

        $now = new DateTime('now', new DateTimeZone('UTC'));

        if ($expired) {
            $expiresAt = clone $now;
            $expiresAt->sub(new DateInterval('P1D')); // 1 day in the past
        } else {
            $expiresAt = clone $now;
            $expiresAt->add(new DateInterval('P30D')); // 30 days in the future
        }

        $device->setExpiresAt($expiresAt);
        $device->setIsRevoked($revoked);
        $device->setDeviceIdentifier($this->service->generateDeviceIdentifier('sometoken'));
        $device->setIpAddress('127.0.0.1');
        $device->setTrustedAt($now);
        $device->setLastSeenAt(clone $now);

        return $device;
    }
}
