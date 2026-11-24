<?php namespace Tests;
/**
 * Copyright 2019 OpenStack Foundation
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

use App\libs\Auth\Models\IGroupSlugs;
use App\libs\OAuth2\IUserScopes;
use Auth\Group;
use App\libs\Auth\Factories\UserFactory;
use App\Mail\UserEmailVerificationRequest;
use Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class OAuth2UserUpdateApiTest
 * @package Tests
 */

final class OAuth2UserUpdateApiTest extends OAuth2ProtectedApiTestCase
{
    public function testUserCreate()
    {
        $group_name = IGroupSlugs::RawUsersGroup;
        $group = EntityManager::getRepository(Group::class)->findOneBy(['name' => $group_name]);
        $first_name = 'test_'. str_random(16);

        $data = [
            'first_name'    => $first_name,
            'last_name'     => 'test_'. str_random(16),
            'email'         => 'test_'. str_random(16) . '@test.com',
            'company'       => 'test_'. str_random(16),
            'groups'        => [$group->getId()],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action
        (
            "POST",
            "Api\\OAuth2\\OAuth2UserApiController@create",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();
        $response = json_decode($content);
        $this->assertTrue($response->first_name == $first_name);
        $this->assertCount(1, $response->groups);
        $this->assertEquals($group_name, $response->groups[0]);
    }

    public function testUserUpdate()
    {
        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $this->assertTrue(!is_null($user));

        $new_first_name = 'test_'. str_random(16);

        $data = [
            'first_name'    => $new_first_name,
            'last_name'     => 'test_'. str_random(16),
            'company'       => 'test_'. str_random(16),
        ];

        $params = [
            'id'   => $user->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action
        (
            "PUT",
            "Api\\OAuth2\\OAuth2UserApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();
        $response = json_decode($content);
        $this->assertTrue($response->first_name == $new_first_name);
    }

    /**
     * @return \stdClass
     */
    private function getUserRow(int $id): \stdClass
    {
        $row = DB::table('users')->where('id', $id)->first();
        $this->assertNotNull($row);
        return $row;
    }

    /**
     * @return array
     */
    private function getJsonHeaders(): array
    {
        return [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];
    }

    /**
     * @return User a user with a verified email, so its spam type is Ham
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
        $this->assertEquals(User::SpamTypeHam, $this->getUserRow($user->getId())->spam_type);
        EntityManager::clear();
        // the requests read the user from the database, like in production
        $this->be(EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']), 'web');
        return EntityManager::getRepository(User::class)->find($user->getId());
    }

    /**
     * A verified user (spam type Ham) that changes the email or sets a bio must get it stored, and be
     * re classified (spam type None).
     */
    public function testUserUpdateEmailAndBioOfVerifiedUserIsStored()
    {
        $user      = $this->buildVerifiedUser();
        $new_email = sprintf('new.%s@test.com', strtolower(str_random(12)));

        $response = $this->action
        (
            "PUT",
            "Api\\OAuth2\\OAuth2UserApiController@update",
            ['id' => $user->getId()],
            [],
            [],
            [],
            $this->getJsonHeaders(),
            json_encode(['first_name' => 'Renamed', 'email' => $new_email, 'bio' => 'a non empty bio'])
        );

        $this->assertResponseStatus(201);

        $row = $this->getUserRow($user->getId());
        $this->assertEquals('Renamed', $row->first_name);
        $this->assertEquals($new_email, $row->email);
        $this->assertEquals('a non empty bio', $row->bio);
        $this->assertEquals(0, $row->email_verified);
        $this->assertEquals(User::SpamTypeNone, $row->spam_type);

        Mail::assertQueued(UserEmailVerificationRequest::class, 1);
        Mail::assertQueued(UserEmailVerificationRequest::class, function (UserEmailVerificationRequest $mail) use ($new_email) {
            return $mail->user_email === $new_email;
        });
    }

    /**
     * PUT /api/v1/users/me, sent by a verified user.
     */
    public function testUserUpdateMeBioOfVerifiedUserIsStored()
    {
        $me = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);
        // the seeded user is verified, make sure it is classified as Ham
        DB::table('users')->where('id', $me->getId())->update(['spam_type' => User::SpamTypeHam]);
        EntityManager::clear();

        $this->json
        (
            "PUT",
            // the bearer token audience is the app url, the request must target that host
            $this->current_realm . "/api/v1/users/me",
            ['first_name' => 'Renamed', 'bio' => 'a non empty bio'],
            $this->getJsonHeaders()
        );

        $this->assertResponseStatus(201);

        $row = $this->getUserRow($me->getId());
        $this->assertEquals('Renamed', $row->first_name);
        $this->assertEquals('a non empty bio', $row->bio);
        $this->assertEquals(User::SpamTypeNone, $row->spam_type);
    }

    protected function getScopes()
    {
        return [
            "openid",
            IUserScopes::Write,
            IUserScopes::MeWrite,
        ];
    }
}
