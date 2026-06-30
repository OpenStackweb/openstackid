<?php namespace Tests\unit\MFA;

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

use Auth\Exceptions\AuthenticationException;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Models\OAuth2\Client;
use Strategies\MFA\AbstractMFAChallengeStrategy;
use Tests\TestCase;

class AbstractMFAChallengeStrategyTest extends TestCase
{
    private AbstractMFAChallengeStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $repo = \Mockery::mock(IUserRecoveryCodeRepository::class);
        $this->strategy = new class($repo) extends AbstractMFAChallengeStrategy {
            public function issueChallenge(User $user, ?Client $client, bool $remember): array { return []; }
            public function verifyChallenge(User $user, string $code, ?Client $client = null): void {}
            public function resendChallenge(User $user, ?Client $client, bool $remember): array { return []; }
            public function exposeStorePendingState(int $userId, bool $remember): void {
                $this->storePendingState($userId, $remember);
            }
        };
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testGetPendingState_withValidSession_returnsState(): void
    {
        $this->strategy->exposeStorePendingState(42, true);

        $state = $this->strategy->getPendingState();

        $this->assertNotNull($state);
        $this->assertSame(42, $state['user_id']);
        $this->assertTrue($state['remember']);
        $this->assertArrayHasKey('pending_at', $state);
    }

    public function testGetPendingState_withExpiredSession_returnsNull(): void
    {
        Session::put('2fa_pending_user_id', 99);
        Session::put('2fa_pending_at', time() - 301);
        Session::put('2fa_remember', false);

        $state = $this->strategy->getPendingState();

        $this->assertNull($state);
        $this->assertNull(Session::get('2fa_pending_user_id'));
    }

    public function testGetPendingState_withMissingSession_returnsNull(): void
    {
        $state = $this->strategy->getPendingState();

        $this->assertNull($state);
    }

    public function testClearPendingState_removesAllSessionKeys(): void
    {
        Session::put('2fa_pending_user_id', 7);
        Session::put('2fa_pending_at', time());
        Session::put('2fa_remember', true);
        Session::put('2fa_recovery_attempts', 1);

        $this->strategy->clearPendingState();

        $this->assertNull(Session::get('2fa_pending_user_id'));
        $this->assertNull(Session::get('2fa_pending_at'));
        $this->assertNull(Session::get('2fa_remember'));
        $this->assertNull(Session::get('2fa_recovery_attempts'));
    }

    public function testVerifyRecoveryCode_withMatchingCode_marksAsUsed(): void
    {
        $user = new User();
        $code = 'VALID-CODE';

        $recoveryCode = \Mockery::mock(\App\libs\Auth\Models\UserRecoveryCode::class);
        $recoveryCode->shouldReceive('getCodeHash')->andReturn(Hash::make($code));
        $recoveryCode->shouldReceive('isUsed')->andReturn(false);
        $recoveryCode->shouldReceive('markUsed')->once();

        $repo = \Mockery::mock(IUserRecoveryCodeRepository::class);
        $repo->shouldReceive('getUnusedByUser')->with($user)->andReturn([$recoveryCode]);
        // Lock is taken before mutating (regression guard for 3357348455).
        $repo->shouldReceive('refreshExclusiveLock')->with($recoveryCode)->once();

        $strategy = new class($repo) extends AbstractMFAChallengeStrategy {
            public function issueChallenge(User $user, ?Client $client, bool $remember): array { return []; }
            public function verifyChallenge(User $user, string $code, ?Client $client = null): void {}
            public function resendChallenge(User $user, ?Client $client, bool $remember): array { return []; }
        };

        $strategy->verifyRecoveryCode($user, $code);
        $this->addToAssertionCount(1); // markUsed()->once() verified by Mockery in tearDown
    }

    public function testVerifyRecoveryCode_locksAndRejects_whenUsedAfterLock(): void
    {
        $user = new User();
        $code = 'VALID-CODE';

        // Hash matches, but a concurrent winner marked it used; the lock+recheck
        // must reject without double-spending (regression guard for 3357348455).
        $recoveryCode = \Mockery::mock(\App\libs\Auth\Models\UserRecoveryCode::class);
        $recoveryCode->shouldReceive('getCodeHash')->andReturn(Hash::make($code));
        $recoveryCode->shouldReceive('isUsed')->andReturn(true);
        $recoveryCode->shouldNotReceive('markUsed');

        $repo = \Mockery::mock(IUserRecoveryCodeRepository::class);
        $repo->shouldReceive('getUnusedByUser')->with($user)->andReturn([$recoveryCode]);
        $repo->shouldReceive('refreshExclusiveLock')->with($recoveryCode)->once();
        $repo->shouldNotReceive('add');

        $strategy = new class($repo) extends AbstractMFAChallengeStrategy {
            public function issueChallenge(User $user, ?Client $client, bool $remember): array { return []; }
            public function verifyChallenge(User $user, string $code, ?Client $client = null): void {}
            public function resendChallenge(User $user, ?Client $client, bool $remember): array { return []; }
        };

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("Invalid recovery code.");
        $strategy->verifyRecoveryCode($user, $code);
    }

    public function testVerifyRecoveryCode_withNonMatchingCode_throwsException(): void
    {
        $user = new User();

        $recoveryCode = \Mockery::mock(\App\libs\Auth\Models\UserRecoveryCode::class);
        $recoveryCode->shouldReceive('getCodeHash')->andReturn(Hash::make('CORRECT-CODE'));
        $recoveryCode->shouldNotReceive('markUsed');

        $repo = \Mockery::mock(IUserRecoveryCodeRepository::class);
        $repo->shouldReceive('getUnusedByUser')->andReturn([$recoveryCode]);

        $strategy = new class($repo) extends AbstractMFAChallengeStrategy {
            public function issueChallenge(User $user, ?Client $client, bool $remember): array { return []; }
            public function verifyChallenge(User $user, string $code, ?Client $client = null): void {}
            public function resendChallenge(User $user, ?Client $client, bool $remember): array { return []; }
        };

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("Invalid recovery code.");
        $strategy->verifyRecoveryCode($user, 'WRONG-CODE');
    }

    public function testVerifyRecoveryCode_withAllCodesUsed_throwsException(): void
    {
        $user = new User();

        $repo = \Mockery::mock(IUserRecoveryCodeRepository::class);
        $repo->shouldReceive('getUnusedByUser')->andReturn([]);

        $strategy = new class($repo) extends AbstractMFAChallengeStrategy {
            public function issueChallenge(User $user, ?Client $client, bool $remember): array { return []; }
            public function verifyChallenge(User $user, string $code, ?Client $client = null): void {}
            public function resendChallenge(User $user, ?Client $client, bool $remember): array { return []; }
        };

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("Invalid recovery code.");
        $strategy->verifyRecoveryCode($user, 'ANY-CODE');
    }
}
