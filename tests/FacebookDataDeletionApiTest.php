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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class FacebookDataDeletionApiTest
 * @package Tests
 */
final class FacebookDataDeletionApiTest extends BrowserKitTestCase
{
    const CallbackUri = '/api/public/v1/facebook/data-deletion';

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        DB::table('facebook_deletion_requests')->delete();
    }

    private function buildSignedRequest(string $user_id): string
    {
        $secret = Config::get('services.facebook.client_secret');
        $payload = ['algorithm' => 'HMAC-SHA256', 'user_id' => $user_id];
        $encoded_payload = $this->base64UrlEncode(json_encode($payload));
        $sig = hash_hmac('sha256', $encoded_payload, $secret, true);
        $encoded_sig = $this->base64UrlEncode($sig);
        return $encoded_sig . '.' . $encoded_payload;
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private function linkSeededUserToFacebook(string $asid): User
    {
        $user_repository = EntityManager::getRepository(User::class);
        $user = $user_repository->findOneBy(["identifier" => 'sebastian.marcet']);
        $user->setExternalId($asid);
        $user->setExternalProvider('facebook');
        $user->setExternalPic('https://graph.facebook.com/pic.jpg');
        EntityManager::persist($user);
        EntityManager::flush();
        return $user;
    }

    public function testMatchedUserIsUnlinkedAndReturnsConfirmation(): void
    {
        $asid = '218471001';
        $user = $this->linkSeededUserToFacebook($asid);
        $sr = $this->buildSignedRequest($asid);

        $response = $this->post(self::CallbackUri, ['signed_request' => $sr]);

        if ($response->response->getStatusCode() !== 200) {
            fwrite(STDERR, "\n===DEBUG RESPONSE BODY===\n" . $response->response->getContent() . "\n===END DEBUG===\n");
        }

        $this->assertResponseStatus(200);
        $json = json_decode($response->response->getContent(), true);
        $this->assertNotEmpty($json['confirmation_code']);
        $this->assertNotEmpty($json['url']);

        $reloaded = EntityManager::getRepository(User::class)->getById($user->getId());
        $this->assertNull($reloaded->getExternalId());
        $this->assertNull($reloaded->getExternalProvider());
        $this->assertNull($reloaded->getExternalPic());

        $row = DB::table('facebook_deletion_requests')->where('external_id', $asid)->first();
        $this->assertNotNull($row);
        $this->assertSame('completed', $row->status);
        $this->assertSame($user->getId(), $row->user_id);
    }

    public function testUnmatchedAsidReturns200AndPersistsNotFoundRow(): void
    {
        $asid = 'no-such-user-999999';
        $sr = $this->buildSignedRequest($asid);

        $response = $this->post(self::CallbackUri, ['signed_request' => $sr]);

        $this->assertResponseStatus(200);
        $json = json_decode($response->response->getContent(), true);
        $this->assertNotEmpty($json['confirmation_code']);

        $row = DB::table('facebook_deletion_requests')->where('external_id', $asid)->first();
        $this->assertNotNull($row);
        $this->assertSame('not_found', $row->status);
        $this->assertNull($row->user_id);
    }

    public function testTamperedSignatureReturns400(): void
    {
        $sr = $this->buildSignedRequest('218471001');
        [, $payload] = explode('.', $sr, 2);
        $tampered = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.' . $payload;

        $response = $this->post(self::CallbackUri, ['signed_request' => $tampered]);

        $this->assertResponseStatus(400);
    }

    public function testStatusPageForRealConfirmationCodeReturns200(): void
    {
        $asid = '218471002';
        $sr = $this->buildSignedRequest($asid);
        $response = $this->post(self::CallbackUri, ['signed_request' => $sr]);
        $json = json_decode($response->response->getContent(), true);
        $confirmation_code = $json['confirmation_code'];

        $status_response = $this->get(self::CallbackUri . '/status/' . $confirmation_code);

        $this->assertResponseStatus(200);
        $status_response->see($confirmation_code);
    }

    public function testStatusPageForUnknownCodeReturns404(): void
    {
        $this->get(self::CallbackUri . '/status/does-not-exist');

        $this->assertResponseStatus(404);
    }

    public function testDuplicateSubmissionIsIdempotent(): void
    {
        $asid = '218471003';
        $sr = $this->buildSignedRequest($asid);

        $first = $this->post(self::CallbackUri, ['signed_request' => $sr]);
        $first_json = json_decode($first->response->getContent(), true);

        $second = $this->post(self::CallbackUri, ['signed_request' => $sr]);
        $second_json = json_decode($second->response->getContent(), true);

        $this->assertResponseStatus(200);
        $this->assertSame($first_json['confirmation_code'], $second_json['confirmation_code']);

        $count = DB::table('facebook_deletion_requests')->where('external_id', $asid)->count();
        $this->assertSame(1, $count);
    }
}
