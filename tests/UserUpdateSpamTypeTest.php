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
use App\Mail\UserEmailVerificationRequest;
use Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class UserUpdateSpamTypeTest
 *
 * A user with a spam type different from None (every user with a verified email is Ham) that changes
 * the email or sets a bio gets the spam type reset by User::preUpdate. That used to flush from
 * inside the flush that was saving the user, so the response was a 201 but only the spam type was stored.
 *
 * These tests run with the whole middleware stack and read the database directly (not the entity manager)
 * to see what was really stored.
 *
 * @package Tests
 */
final class UserUpdateSpamTypeTest extends OpenStackIDBaseTestCase
{
    /**
     * Secure requests, so the ssl middleware does not redirect when server.ssl_enabled is on.
     * @var string
     */
    protected $baseUrl = 'https://local.idp.com';

    /**
     * @var User
     */
    private User $admin;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $this->admin = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);
        $this->be($this->admin, 'web');
    }

    /**
     * @return User a user with a verified email, so spam_type is Ham
     */
    private function buildVerifiedUser(): User
    {
        $suffix = strtolower(str_random(12));

        $user = UserFactory::build([
            'first_name'     => 'Ham',
            'last_name'      => 'User',
            'email'          => sprintf('ham.%s@test.com', $suffix),
            'password'       => '1Qaz2wsx!',
            'active'         => true,
            'email_verified' => true,
            'identifier'     => 'ham.' . $suffix,
        ]);

        EntityManager::persist($user);
        EntityManager::flush();

        $row = $this->getUserRow($user->getId());
        $this->assertEquals(User::SpamTypeHam, $row->spam_type, 'precondition: a verified user is Ham');
        $this->assertEquals(1, $row->email_verified, 'precondition: email is verified');

        // from here on the requests must read the user from the database, like a real request does
        EntityManager::clear();
        $this->be(EntityManager::getRepository(User::class)->find($this->admin->getId()), 'web');

        return EntityManager::getRepository(User::class)->find($user->getId());
    }

    private function getUserRow(int $id): \stdClass
    {
        $row = DB::table('users')->where('id', $id)->first();
        $this->assertNotNull($row);
        return $row;
    }

    private function assertNoVerificationEmailQueued(): void
    {
        Mail::assertNotQueued(UserEmailVerificationRequest::class);
    }

    // -------------------------------------------------------------------------
    // PUT admin/api/v1/users/{id}
    // -------------------------------------------------------------------------

    public function testAdminUpdateFirstNameAndEmailIsStored(): void
    {
        $user      = $this->buildVerifiedUser();
        $new_email = sprintf('new.%s@test.com', strtolower(str_random(12)));

        $this->put('/admin/api/v1/users/' . $user->getId(), [
            'first_name' => 'Renamed',
            'email'      => $new_email,
        ])->assertResponseStatus(201);

        $row = $this->getUserRow($user->getId());
        $this->assertEquals('Renamed', $row->first_name);
        $this->assertEquals($new_email, $row->email);
        $this->assertEquals(0, $row->email_verified);
        // the re classification still happens
        $this->assertEquals(User::SpamTypeNone, $row->spam_type);
    }

    public function testAdminUpdateFirstNameAndBioIsStored(): void
    {
        $user = $this->buildVerifiedUser();

        $this->put('/admin/api/v1/users/' . $user->getId(), [
            'first_name' => 'Renamed',
            'bio'        => 'a non empty bio',
        ])->assertResponseStatus(201);

        $row = $this->getUserRow($user->getId());
        $this->assertEquals('Renamed', $row->first_name);
        $this->assertEquals('a non empty bio', $row->bio);
        $this->assertEquals(User::SpamTypeNone, $row->spam_type);
    }

    public function testAdminUpdateWithoutSpamTypeTriggerKeepsSpamType(): void
    {
        $user = $this->buildVerifiedUser();

        $this->put('/admin/api/v1/users/' . $user->getId(), [
            'first_name' => 'Renamed',
        ])->assertResponseStatus(201);

        $row = $this->getUserRow($user->getId());
        $this->assertEquals('Renamed', $row->first_name);
        // neither the email nor the bio changed, so it is not re classified
        $this->assertEquals(User::SpamTypeHam, $row->spam_type);
    }

    public function testAdminUpdateEmailQueuesOneVerificationEmailToTheNewAddress(): void
    {
        $user      = $this->buildVerifiedUser();
        $new_email = sprintf('new.%s@test.com', strtolower(str_random(12)));

        $this->put('/admin/api/v1/users/' . $user->getId(), [
            'first_name' => 'Renamed',
            'email'      => $new_email,
        ])->assertResponseStatus(201);

        Mail::assertQueued(UserEmailVerificationRequest::class, 1);
        Mail::assertQueued(UserEmailVerificationRequest::class, function (UserEmailVerificationRequest $mail) use ($new_email) {
            return $mail->user_email === $new_email;
        });

        // the link of that email verifies the address that was stored
        $mails = Mail::queued(UserEmailVerificationRequest::class);
        $link  = $mails->first()->verification_link;
        $this->assertNotEmpty($link);

        $this->get($link);

        $row = $this->getUserRow($user->getId());
        $this->assertEquals($new_email, $row->email);
        $this->assertEquals(1, $row->email_verified);
    }

    public function testAdminUpdateRejectedQueuesNoVerificationEmail(): void
    {
        $user = $this->buildVerifiedUser();

        // an email that belongs to another user is rejected with 412
        $this->put('/admin/api/v1/users/' . $user->getId(), [
            'first_name' => 'Renamed',
            'email'      => $this->admin->getEmail(),
        ])->assertResponseStatus(412);

        $row = $this->getUserRow($user->getId());
        $this->assertEquals('Ham', $row->first_name);
        $this->assertEquals($user->getEmail(), $row->email);
        $this->assertEquals(User::SpamTypeHam, $row->spam_type);
        $this->assertNoVerificationEmailQueued();
    }

    public function testAdminUpdateWithSpamTypeNoneStillStored(): void
    {
        // control case, this one worked before the fix
        $user = $this->buildVerifiedUser();
        DB::table('users')->where('id', $user->getId())->update(['spam_type' => User::SpamTypeNone]);
        EntityManager::clear();
        $this->be(EntityManager::getRepository(User::class)->find($this->admin->getId()), 'web');

        $this->put('/admin/api/v1/users/' . $user->getId(), [
            'first_name' => 'Renamed',
            'bio'        => 'a non empty bio',
        ])->assertResponseStatus(201);

        $row = $this->getUserRow($user->getId());
        $this->assertEquals('Renamed', $row->first_name);
        $this->assertEquals('a non empty bio', $row->bio);
    }

    // -------------------------------------------------------------------------
    // PUT admin/api/v1/users/me
    // -------------------------------------------------------------------------

    public function testMeUpdateFirstNameAndBioIsStored(): void
    {
        $user = $this->buildVerifiedUser();
        $this->be($user, 'web');

        $this->put('/admin/api/v1/users/me', [
            'first_name' => 'Renamed',
            'bio'        => 'a non empty bio',
        ])->assertResponseStatus(201);

        $row = $this->getUserRow($user->getId());
        $this->assertEquals('Renamed', $row->first_name);
        $this->assertEquals('a non empty bio', $row->bio);
        $this->assertEquals(User::SpamTypeNone, $row->spam_type);
    }

    public function testMeUpdateFirstNameAndEmailIsStored(): void
    {
        $user      = $this->buildVerifiedUser();
        $new_email = sprintf('new.%s@test.com', strtolower(str_random(12)));
        $this->be($user, 'web');

        $this->put('/admin/api/v1/users/me', [
            'first_name' => 'Renamed',
            'email'      => $new_email,
        ])->assertResponseStatus(201);

        $row = $this->getUserRow($user->getId());
        $this->assertEquals('Renamed', $row->first_name);
        $this->assertEquals($new_email, $row->email);
        $this->assertEquals(0, $row->email_verified);
        $this->assertEquals(User::SpamTypeNone, $row->spam_type);

        Mail::assertQueued(UserEmailVerificationRequest::class, 1);
        Mail::assertQueued(UserEmailVerificationRequest::class, function (UserEmailVerificationRequest $mail) use ($new_email) {
            return $mail->user_email === $new_email;
        });
    }
}
