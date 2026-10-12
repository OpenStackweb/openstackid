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

use App\Events\UserPasswordResetSuccessful;
use App\Jobs\EmitAuditLogJob;
use App\Jobs\RevokeUserGrantsOnExplicitLogout;
use App\Jobs\RevokeUserGrantsOnPasswordChange;
use App\Jobs\RevokeUserGrantsOnSessionRevocation;
use Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Mockery;
use OAuth2\Services\ITokenService;

/**
 * Class PasswordChangeRevokeTokenTest
 * Tests for the security fix: revoke tokens and invalidate sessions on password change.
 * @package Tests
 */
final class PasswordChangeRevokeTokenTest extends OpenStackIDBaseTestCase
{
    /**
     * @var User
     */
    private User $test_user;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $user_repository  = EntityManager::getRepository(User::class);
        $this->test_user  = $user_repository->findOneBy(['identifier' => 'sebastian.marcet']);
        $this->be($this->test_user, 'web');
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Test 1: Dispatching UserPasswordResetSuccessful fires RevokeUserGrantsOnPasswordChange
    // -------------------------------------------------------------------------

    /**
     * When a UserPasswordResetSuccessful event is fired the EventServiceProvider
     * listener must schedule a RevokeUserGrantsOnPasswordChange job for all clients.
     */
    public function testPasswordResetEventDispatchesRevokeUserGrantsJob(): void
    {
        Event::dispatch(new UserPasswordResetSuccessful($this->test_user->getId()));

        // afterResponse() registers a terminating callback; fire it now.
        app()->terminate();

        Queue::assertPushed(RevokeUserGrantsOnPasswordChange::class, function (RevokeUserGrantsOnPasswordChange $job) {
            $ref      = new \ReflectionClass(\App\Jobs\RevokeUserGrants::class);
            $userId   = $ref->getProperty('user_id');
            $clientId = $ref->getProperty('client_id');
            $userId->setAccessible(true);
            $clientId->setAccessible(true);

            return $userId->getValue($job)   === $this->test_user->getId()
                && $clientId->getValue($job) === null;
        });
    }

    // -------------------------------------------------------------------------
    // Test 2: PUT /admin/api/v1/users/me with password schedules RevokeUserGrantsOnPasswordChange
    // -------------------------------------------------------------------------

    /**
     * Posting a new password via the profile API must schedule a
     * RevokeUserGrantsOnPasswordChange job so tokens from other sessions are revoked.
     */
    public function testProfilePasswordChangePutsRevokeUserGrantsJobOnQueue(): void
    {
        $payload = [
            'first_name'            => $this->test_user->getFirstName(),
            'last_name'             => $this->test_user->getLastName(),
            'email'                 => $this->test_user->getEmail(),
            'current_password'      => '1Qaz2wsx!9',
            'password'              => 'NewP@ssw0rd!99',
            'password_confirmation' => 'NewP@ssw0rd!99',
        ];

        $this->put('/admin/api/v1/users/me', $payload)
             ->assertResponseStatus(201);

        // The listener for UserPasswordResetSuccessful uses afterResponse().
        app()->terminate();

        Queue::assertPushed(RevokeUserGrantsOnPasswordChange::class, function (RevokeUserGrantsOnPasswordChange $job) {
            $ref      = new \ReflectionClass(\App\Jobs\RevokeUserGrants::class);
            $clientId = $ref->getProperty('client_id');
            $clientId->setAccessible(true);

            return $clientId->getValue($job) === null;
        });
    }

    // -------------------------------------------------------------------------
    // Test 3: setPassword() rotates the remember_token
    // -------------------------------------------------------------------------

    /**
     * Calling User::setPassword() must rotate the remember_token so that
     * "remember me" cookies issued to other devices become invalid.
     */
    public function testSetPasswordRotatesRememberToken(): void
    {
        $original_token = $this->test_user->getRememberToken();

        $this->test_user->setPassword('AnotherS3cur3!Pass');

        $new_token = $this->test_user->getRememberToken();

        $this->assertNotEmpty($new_token);
        $this->assertNotEquals($original_token, $new_token);
    }

    // -------------------------------------------------------------------------
    // Test 4: Current session is preserved (regenerated) after password change
    // -------------------------------------------------------------------------

    /**
     * After a profile password change the API response must be successful and
     * the user must remain authenticated (session is regenerated, not destroyed).
     */
    public function testCurrentSessionPreservedAfterPasswordChange(): void
    {
        $payload = [
            'first_name'            => $this->test_user->getFirstName(),
            'last_name'             => $this->test_user->getLastName(),
            'email'                 => $this->test_user->getEmail(),
            'current_password'      => '1Qaz2wsx!9',
            'password'              => 'Preserved@Sess10n!',
            'password_confirmation' => 'Preserved@Sess10n!',
        ];

        $this->put('/admin/api/v1/users/me', $payload)
             ->assertResponseStatus(201);

        // The currently authenticated user must still be set after the call.
        $this->assertTrue(\Illuminate\Support\Facades\Auth::check());
        $this->assertEquals($this->test_user->getId(), \Illuminate\Support\Facades\Auth::id());
    }

    // -------------------------------------------------------------------------
    // Test 5: DELETE /admin/api/v1/users/me/tokens schedules RevokeUserGrantsOnSessionRevocation
    // -------------------------------------------------------------------------

    /**
     * The "sign out all other devices" endpoint must respond with 204 and
     * schedule a RevokeUserGrantsOnSessionRevocation job for all clients.
     */
    public function testBulkRevokeEndpointSchedulesRevokeUserGrantsJob(): void
    {
        $this->delete('/admin/api/v1/users/me/tokens')
             ->assertResponseStatus(204);

        app()->terminate();

        Queue::assertPushed(RevokeUserGrantsOnSessionRevocation::class, function (RevokeUserGrantsOnSessionRevocation $job) {
            $ref      = new \ReflectionClass(\App\Jobs\RevokeUserGrants::class);
            $userId   = $ref->getProperty('user_id');
            $clientId = $ref->getProperty('client_id');
            $userId->setAccessible(true);
            $clientId->setAccessible(true);

            return $userId->getValue($job)   === $this->test_user->getId()
                && $clientId->getValue($job) === null;
        });
    }

    // -------------------------------------------------------------------------
    // Test 6: RevokeUserGrantsOnExplicitLogout passes client_id to token service
    // -------------------------------------------------------------------------

    /**
     * When constructed with a specific client_id the job must call
     * ITokenService::revokeUsersToken($user_id, $client_id).
     */
    public function testRevokeUserGrantsJobPassesClientIdToTokenService(): void
    {
        $client_id = 'test-client-id';

        $mock_service = Mockery::mock(ITokenService::class);
        $mock_service->shouldReceive('revokeUsersToken')
                     ->once()
                     ->with($this->test_user->getId(), $client_id);

        $job = new RevokeUserGrantsOnExplicitLogout($this->test_user, $client_id);
        $job->handle($mock_service);
    }

    // -------------------------------------------------------------------------
    // Test 7: RevokeUserGrantsOnPasswordChange passes null client_id to token service
    // -------------------------------------------------------------------------

    /**
     * RevokeUserGrantsOnPasswordChange must call
     * ITokenService::revokeUsersToken($user_id, null), revoking across all clients.
     */
    public function testRevokeUserGrantsJobPassesNullClientIdToTokenService(): void
    {
        $mock_service = Mockery::mock(ITokenService::class);
        $mock_service->shouldReceive('revokeUsersToken')
                     ->once()
                     ->with($this->test_user->getId(), null);

        $job = new RevokeUserGrantsOnPasswordChange($this->test_user);
        $job->handle($mock_service);
    }

    // -------------------------------------------------------------------------
    // Test 8a: OTEL audit job is dispatched when opentelemetry is enabled
    // -------------------------------------------------------------------------

    /**
     * When opentelemetry.enabled is true, the job must dispatch an EmitAuditLogJob
     * with the correct log message and audit fields.
     */
    public function testOtelAuditJobDispatchedWhenOpentelemetryEnabled(): void
    {
        Config::set('opentelemetry.enabled', true);

        $mock_service = Mockery::mock(ITokenService::class);
        $mock_service->shouldReceive('revokeUsersToken')->once();

        $job = new RevokeUserGrantsOnPasswordChange($this->test_user);
        $job->handle($mock_service);

        Queue::assertPushed(EmitAuditLogJob::class, function (EmitAuditLogJob $emitted) {
            return $emitted->logMessage === 'audit.security.tokens_revoked'
                && $emitted->auditData['audit.action']    === 'revoke_tokens'
                && $emitted->auditData['audit.entity']    === 'User'
                && $emitted->auditData['audit.entity_id'] === (string) $this->test_user->getId()
                && $emitted->auditData['audit.reason']    === 'password change'
                && $emitted->auditData['auth.user.id']    === $this->test_user->getId();
        });
    }

    // -------------------------------------------------------------------------
    // Test 8b: OTEL audit job is NOT dispatched when opentelemetry is disabled
    // -------------------------------------------------------------------------

    /**
     * When opentelemetry.enabled is false, the job must not dispatch any EmitAuditLogJob.
     */
    public function testOtelAuditJobNotDispatchedWhenOpentelemetryDisabled(): void
    {
        Config::set('opentelemetry.enabled', false);

        $mock_service = Mockery::mock(ITokenService::class);
        $mock_service->shouldReceive('revokeUsersToken')->once();

        $job = new RevokeUserGrantsOnPasswordChange($this->test_user);
        $job->handle($mock_service);

        Queue::assertNotPushed(EmitAuditLogJob::class);
    }
}
