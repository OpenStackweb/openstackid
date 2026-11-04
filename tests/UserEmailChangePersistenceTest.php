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
use App\Mail\UserEmailVerificationRequest;
use App\Services\Auth\IUserService as IAuthUserService;
use Auth\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LaravelDoctrine\ORM\Facades\EntityManager;
use OpenId\Services\IUserService;

/**
 * Class UserEmailChangePersistenceTest
 *
 * Regression: for a user whose spam_type is Ham/Spam, User::preUpdate used to
 * dispatch UserSpamStateUpdated from inside the running flush. Its listener
 * opened a nested transaction + flush that wiped the outer entity change set,
 * so the email / bio / verification token changes were silently dropped.
 *
 * @package Tests
 */
final class UserEmailChangePersistenceTest extends OpenStackIDBaseTestCase
{
    private const FormerEmail = 'sebastian@tipit.net';

    private function setSpamType(string $spam_type): int
    {
        $id = (int)DB::table('users')->where('email', self::FormerEmail)->value('id');
        $this->assertGreaterThan(0, $id);
        DB::table('users')->where('id', $id)->update(['spam_type' => $spam_type]);
        EntityManager::clear();
        return $id;
    }

    private function reload(int $id): User
    {
        EntityManager::clear();
        $user = EntityManager::getRepository(User::class)->find($id);
        $this->assertInstanceOf(User::class, $user);
        return $user;
    }

    /**
     * @return string the token embedded in the queued verification link
     */
    private function queuedVerificationToken(): string
    {
        $link = null;
        Mail::assertQueued(UserEmailVerificationRequest::class, function ($mail) use (&$link) {
            $link = $mail->verification_link;
            return true;
        });
        $this->assertNotEmpty($link);
        return basename(parse_url($link, PHP_URL_PATH));
    }

    public function testHamUserEmailChangeIsPersistedOnFirstAttempt(): void
    {
        $id = $this->setSpamType(User::SpamTypeHam);
        $new_email = 'new_' . strtolower(str_random(12)) . '@test.com';

        App::make(IUserService::class)->update($id, ['email' => $new_email]);

        $user = $this->reload($id);
        $this->assertEquals($new_email, $user->getEmail());
        $this->assertFalse($user->isEmailVerified());
        $this->assertNotNull(DB::table('users')->where('id', $id)->value('email_verified_token_hash'));
        $this->assertEquals(User::SpamTypeNone, $user->getSpamType());

        $token = $this->queuedVerificationToken();
        $verified = App::make(IAuthUserService::class)->verifyEmail($token);
        $this->assertTrue($verified->isEmailVerified());
        $this->assertEquals($new_email, $this->reload($id)->getEmail());
    }

    public function testSpamUserEmailChangeIsPersistedOnFirstAttempt(): void
    {
        $id = $this->setSpamType(User::SpamTypeSpam);
        $new_email = 'new_' . strtolower(str_random(12)) . '@test.com';

        App::make(IUserService::class)->update($id, ['email' => $new_email]);

        $user = $this->reload($id);
        $this->assertEquals($new_email, $user->getEmail());
        $this->assertNotNull(DB::table('users')->where('id', $id)->value('email_verified_token_hash'));
    }

    public function testHamUserBioChangeIsPersisted(): void
    {
        $id = $this->setSpamType(User::SpamTypeHam);
        $new_bio = 'bio_' . str_random(24);

        App::make(IUserService::class)->update($id, ['bio' => $new_bio]);

        $user = $this->reload($id);
        $this->assertEquals($new_bio, $user->getBio());
        $this->assertEquals(User::SpamTypeNone, $user->getSpamType());
    }

    public function testSpamReclassificationStillRunsAfterEmailChange(): void
    {
        $id = $this->setSpamType(User::SpamTypeHam);
        $new_email = 'new_' . strtolower(str_random(12)) . '@test.com';

        $dispatched = [];
        \Event::listen(\App\Events\UserSpamStateUpdated::class, function ($event) use (&$dispatched) {
            $dispatched[] = $event->getUserId();
        });

        App::make(IUserService::class)->update($id, ['email' => $new_email]);

        $this->assertContains($id, $dispatched);
    }
}
