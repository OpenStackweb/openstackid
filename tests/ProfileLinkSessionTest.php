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

use Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class ProfileLinkSessionTest
 *
 * Regression tests for the session-overlap bug: clicking a profile link from
 * an email while a different user is already logged in must NOT land on the
 * currently-authenticated user's profile.
 */
final class ProfileLinkSessionTest extends BrowserKitTestCase
{
    private User $user_a;
    private User $user_b;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        Session::start();
        $this->user_a = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);
        $this->user_b = EntityManager::getRepository(User::class)->findOneBy(['identifier' => '2']);
    }

    private function profileLinkFor(User $user): string
    {
        return URL::signedRoute('auth.profile-link', ['user_id' => $user->getId()]);
    }

    /**
     * A tampered or forged signature must be rejected immediately with 403.
     * This prevents an attacker from crafting links for arbitrary user IDs.
     */
    public function testTamperedSignatureReturns403(): void
    {
        $url = $this->profileLinkFor($this->user_a);
        $tampered = preg_replace('/signature=[^&]+/', 'signature=invalid_forged_value', $url);

        $this->call('GET', $tampered);

        $this->assertResponseStatus(403);
    }

    /**
     * An unauthenticated visitor following a valid link is redirected to the
     * login page pre-filled with the email address of the link's owner.
     */
    public function testGuestFollowsLinkRedirectsToLoginWithHint(): void
    {
        $response = $this->call('GET', $this->profileLinkFor($this->user_a));

        $this->assertResponseStatus(302);
        $target = $response->getTargetUrl();
        $this->assertStringContainsString('login', $target);
        $this->assertStringContainsString(urlencode($this->user_a->getEmail()), $target);
    }

    /**
     * The link's owner, already authenticated, is sent straight to their profile
     * without having to log in again.
     */
    public function testOwnerAlreadyLoggedInRedirectsToProfile(): void
    {
        $this->be($this->user_a);

        $response = $this->call('GET', $this->profileLinkFor($this->user_a));

        $this->assertResponseStatus(302);
        $this->assertStringContainsString('profile', $response->getTargetUrl());
    }

    /**
     * Core regression: user B is logged in and follows user A's profile link.
     * Must redirect to the login page (not to user B's profile), pre-filled
     * with user A's email so the correct person can authenticate.
     */
    public function testDifferentUserLoggedInRedirectsToLoginNotProfile(): void
    {
        $this->be($this->user_b);

        $response = $this->call('GET', $this->profileLinkFor($this->user_a));

        $this->assertResponseStatus(302);
        $target = $response->getTargetUrl();
        $this->assertStringNotContainsString('profile', $target);
        $this->assertStringContainsString('login', $target);
        // Hint must point to user A, not user B
        $this->assertStringContainsString(urlencode($this->user_a->getEmail()), $target);
        $this->assertStringNotContainsString(urlencode($this->user_b->getEmail()), $target);
    }

    /**
     * After user B follows user A's link, user B's session is invalidated.
     * Auth::check() must return false — if it were true, user B's profile
     * would still be accessible from the same browser.
     */
    public function testDifferentUserSessionIsInvalidatedAfterFollowingLink(): void
    {
        $this->be($this->user_b);

        $this->call('GET', $this->profileLinkFor($this->user_a));

        $this->assertFalse(Auth::check(), 'User B must be logged out after following user A\'s profile link');
    }
}
