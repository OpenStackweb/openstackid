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
use App\Services\Auth\IDeviceTrustService;
use App\Services\Auth\IRecoveryCodeService;
use Auth\User;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class DisableTwoFactorIntegrationTest
 * IRecoveryCodeService::disableTwoFactor against the real database.
 * @package Tests
 */
final class DisableTwoFactorIntegrationTest extends BrowserKitTestCase
{
    private IRecoveryCodeService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(IRecoveryCodeService::class);

        $user = new User();
        $user->setEmail('disable-2fa@nomail.com');
        $user->setFirstName('Disable');
        $user->setLastName('TwoFactor');
        $user->setIdentifier('disable-2fa@nomail.com');
        $user->setPassword('P@sswordS3cret');
        $user->verifyEmail(false);
        EntityManager::persist($user);
        EntityManager::flush();
        $this->user = $user;

        // enrolled, with a code batch and two trusted devices
        $this->service->enableTwoFactorAndGenerateCodes($user, User::MFAMethod_OTP);
        $devices = $this->app->make(IDeviceTrustService::class);
        $devices->trustDevice($user, 'agent-a', '127.0.0.1');
        $devices->trustDevice($user, 'agent-b', '127.0.0.1');
    }

    private function userRow(): object
    {
        return DB::table('users')->where('id', $this->user->getId())->first();
    }

    public function testDisableClearsEverything(): void
    {
        $this->assertSame(1, (int)$this->userRow()->two_factor_enabled);
        $this->assertSame(10, DB::table('user_recovery_codes')->where('user_id', $this->user->getId())->count());

        $this->service->disableTwoFactor($this->user, null, null);

        $row = $this->userRow();
        $this->assertSame(0, (int)$row->two_factor_enabled);
        $this->assertNull($row->two_factor_enforced_at);
        $this->assertSame(0, DB::table('user_recovery_codes')->where('user_id', $this->user->getId())->count());

        $devices = DB::table('user_trusted_devices')->where('user_id', $this->user->getId())->get();
        $this->assertCount(2, $devices);
        foreach ($devices as $device) {
            $this->assertSame(1, (int)$device->is_revoked);
        }

        $this->assertSame(1, DB::table('two_factor_audit_log')
            ->where('user_id', $this->user->getId())
            ->where('event_type', TwoFactorAuditLog::EventDeviceRevoked)
            ->count());
        // settings_changed is the caller's responsibility
        $this->assertSame(0, DB::table('two_factor_audit_log')
            ->where('user_id', $this->user->getId())
            ->where('event_type', TwoFactorAuditLog::EventSettingsChanged)
            ->count());
    }

    public function testSelfServiceWithWrongPasswordLeavesStateUntouched(): void
    {
        try {
            $this->service->disableTwoFactor($this->user, 'wrong', $this->user);
            $this->fail('ValidationException expected');
        } catch (\models\exceptions\ValidationException $ex) {
        }

        $this->assertSame(1, (int)$this->userRow()->two_factor_enabled);
        $this->assertSame(10, DB::table('user_recovery_codes')->where('user_id', $this->user->getId())->count());
        $this->assertSame(0, DB::table('user_trusted_devices')
            ->where('user_id', $this->user->getId())->where('is_revoked', 1)->count());
    }
}
