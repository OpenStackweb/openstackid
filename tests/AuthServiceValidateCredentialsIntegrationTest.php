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

use Auth\Exceptions\AuthenticationException;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Illuminate\Support\Facades\Auth;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Utils\Services\IAuthService;
use Utils\Services\UtilsServiceCatalog;

/**
 * Class AuthServiceValidateCredentialsIntegrationTest
 * Exercises AuthService::validateCredentials() against the real database and
 * security-checkpoint stack to verify that failed attempts increment the
 * user's login_failed_attempt counter (via LockUserCounterMeasure) and that
 * no session is established on either success or failure.
 */
final class AuthServiceValidateCredentialsIntegrationTest extends OpenStackIDBaseTestCase
{
    // CustomAuthProvider looks up users via IUserRepository::getByEmailOrName(),
    // which currently matches only on the email column — so login uses the email
    // as the "username".
    private const SEEDED_USERNAME = 'sebastian@tipit.net';
    private const SEEDED_PASSWORD = '1Qaz2wsx!';

    private IAuthService $auth_service;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $this->auth_service = $this->app[UtilsServiceCatalog::AuthenticationService];
    }

    /**
     * A failed validateCredentials() call must:
     *  - throw AuthenticationException,
     *  - NOT establish a session (Auth::check() stays false),
     *  - trigger LockUserCounterMeasure so the user's login_failed_attempt counter increments.
     */
    public function testFailedAttempt_incrementsLoginFailedAttemptCounter(): void
    {
        $initial_attempts = $this->getLoginFailedAttempt(self::SEEDED_USERNAME);
        $this->assertFalse(Auth::check(), 'precondition: no authenticated user');

        $threw = false;
        try {
            $this->auth_service->validateCredentials(self::SEEDED_USERNAME, 'wrong-password');
        } catch (AuthenticationException $ex) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected AuthenticationException on wrong password');
        $this->assertFalse(Auth::check(), 'No session should be established after a failed attempt');

        $new_attempts = $this->getLoginFailedAttempt(self::SEEDED_USERNAME);
        $this->assertSame(
            $initial_attempts + 1,
            $new_attempts,
            'login_failed_attempt counter must increment via LockUserCounterMeasure'
        );
    }

    /**
     * A successful validateCredentials() call must return the user without
     * establishing a session — Auth::check() must remain false afterwards.
     */
    public function testSuccessfulValidation_doesNotEstablishSession(): void
    {
        $this->assertFalse(Auth::check(), 'precondition: no authenticated user');

        $user = $this->auth_service->validateCredentials(
            self::SEEDED_USERNAME,
            self::SEEDED_PASSWORD
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse(
            Auth::check(),
            'validateCredentials() must NOT call Auth::login() on success'
        );
    }

    private function getLoginFailedAttempt(string $username): int
    {
        // Clear Doctrine's identity map so we read fresh state from the DB,
        // not a cached in-memory entity from a prior transaction.
        EntityManager::clear();
        $repo = EntityManager::getRepository(User::class);
        /** @var IUserRepository $repo */
        $user = $repo->getByEmailOrName($username);
        $this->assertInstanceOf(User::class, $user, "Seeded user {$username} not found");
        return $user->getLoginFailedAttempt();
    }
}
