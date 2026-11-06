<?php

namespace Tests\unit;

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

use App\Services\Auth\IDeviceTrustService;
use App\Services\Auth\IRecoveryCodeService;
use App\Services\Auth\MFAGateService;
use Auth\User;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Class MFAGateServiceTest
 * @package Tests\unit
 */
final class MFAGateServiceTest extends TestCase
{
    private MFAGateService $service;

    private MockInterface&IDeviceTrustService $deviceTrustService;

    private MockInterface&IRecoveryCodeService $recoveryCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deviceTrustService = Mockery::mock(IDeviceTrustService::class);
        $this->recoveryCodeService = Mockery::mock(IRecoveryCodeService::class);
        // Default: the user already has an acknowledged, usable batch, so the
        // pre-existing device-trust cases below keep their meaning.
        $this->recoveryCodeService->shouldReceive('requiresRecoveryCodeEnrollment')->andReturn(false)->byDefault();
        $this->service = new MFAGateService($this->deviceTrustService, $this->recoveryCodeService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Non-admin/non-enforced user with 2FA disabled returns false
    // -------------------------------------------------------------------------

    public function testRequiresChallengeReturnsFalseWhenUserDoesNotRequire2FA(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('shouldRequire2FA')->once()->andReturn(false);
        $this->deviceTrustService->shouldNotReceive('isDeviceTrusted');

        $this->assertFalse($this->service->requiresChallenge($user, null));
    }

    // -------------------------------------------------------------------------
    // Admin/enforced user with no cookie returns true
    // -------------------------------------------------------------------------

    public function testRequiresChallengeReturnsTrueWhenEnforcedAndNoCookie(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('shouldRequire2FA')->once()->andReturn(true);
        $this->deviceTrustService->shouldReceive('isDeviceTrusted')->once()->with($user, null)->andReturn(false);

        $this->assertTrue($this->service->requiresChallenge($user, null));
    }

    // -------------------------------------------------------------------------
    // Admin/enforced user with trusted device returns false
    // -------------------------------------------------------------------------

    public function testRequiresChallengeReturnsFalseWhenEnforcedAndDeviceTrusted(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('shouldRequire2FA')->once()->andReturn(true);
        $this->deviceTrustService->shouldReceive('isDeviceTrusted')->once()->with($user, 'valid-token')->andReturn(true);

        $this->assertFalse($this->service->requiresChallenge($user, 'valid-token'));
    }

    // -------------------------------------------------------------------------
    // Admin/enforced user with expired/revoked/wrong device returns true
    // -------------------------------------------------------------------------

    public function testRequiresChallengeReturnsTrueWhenEnforcedAndDeviceNotTrusted(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('shouldRequire2FA')->once()->andReturn(true);
        $this->deviceTrustService->shouldReceive('isDeviceTrusted')->once()->with($user, 'expired-token')->andReturn(false);

        $this->assertTrue($this->service->requiresChallenge($user, 'expired-token'));
    }

    // -------------------------------------------------------------------------
    // Empty-string cookie is forwarded as-is (not coerced to null) and treated
    // as untrusted — documents the ?string contract between gate and trust layers
    // -------------------------------------------------------------------------

    public function testRequiresChallengePassesThroughEmptyStringCookieToDeviceTrustService(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('shouldRequire2FA')->once()->andReturn(true);
        $this->deviceTrustService->shouldReceive('isDeviceTrusted')->once()->with($user, '')->andReturn(false);

        $this->assertTrue($this->service->requiresChallenge($user, ''));
    }

    // -------------------------------------------------------------------------
    // Recovery-code enrollment (SDS idp-mfa.md §4.2 step 4): a user with no
    // usable batch is challenged even on a trusted device, and the device row
    // is not consulted (so its last_seen_at is not touched)
    // -------------------------------------------------------------------------

    public function testRequiresChallengeReturnsTrueOnTrustedDeviceWhenEnrollmentRequired(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('shouldRequire2FA')->once()->andReturn(true);
        $this->recoveryCodeService->shouldReceive('requiresRecoveryCodeEnrollment')->once()->with($user)->andReturn(true);
        $this->deviceTrustService->shouldNotReceive('isDeviceTrusted');

        $this->assertTrue($this->service->requiresChallenge($user, 'valid-token'));
    }

    public function testRequiresChallengeSkipsOnTrustedDeviceOnceBatchIsAcknowledged(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('shouldRequire2FA')->once()->andReturn(true);
        $this->recoveryCodeService->shouldReceive('requiresRecoveryCodeEnrollment')->once()->with($user)->andReturn(false);
        $this->deviceTrustService->shouldReceive('isDeviceTrusted')->once()->with($user, 'valid-token')->andReturn(true);

        $this->assertFalse($this->service->requiresChallenge($user, 'valid-token'));
    }

    public function testEnrollmentIsNotEvaluatedForUsersThatDoNotRequire2FA(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('shouldRequire2FA')->once()->andReturn(false);
        $this->recoveryCodeService->shouldNotReceive('requiresRecoveryCodeEnrollment');

        $this->assertFalse($this->service->requiresChallenge($user, 'valid-token'));
    }

    // -------------------------------------------------------------------------
    // Global kill-switch (SDS idp-mfa.md §10.1) is now enforced inside
    // User::shouldRequire2FA() (single source of truth shared with the
    // passwordless-login guard); its coverage lives in
    // Tests\unit\UserTwoFactorTest::testShouldRequire2FA_returnsFalse_whenGloballyDisabled.
    // The gate short-circuiting when shouldRequire2FA() is false is covered by
    // testRequiresChallengeReturnsFalseWhenUserDoesNotRequire2FA above.
    // -------------------------------------------------------------------------
}
