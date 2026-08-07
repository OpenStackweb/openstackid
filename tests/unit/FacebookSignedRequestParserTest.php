<?php namespace Tests\unit;
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
use App\libs\Auth\FacebookSignedRequestParser;
use PHPUnit\Framework\TestCase;

/**
 * Class FacebookSignedRequestParserTest
 * @package Tests\unit
 */
final class FacebookSignedRequestParserTest extends TestCase
{
    const Secret = 'test-app-secret';

    private function buildSignedRequest(array $payload, string $secret, string $algorithm = 'HMAC-SHA256'): string
    {
        $payload['algorithm'] = $algorithm;
        $encoded_payload = $this->base64UrlEncode(json_encode($payload));
        $sig = hash_hmac('sha256', $encoded_payload, $secret, true);
        $encoded_sig = $this->base64UrlEncode($sig);
        return $encoded_sig . '.' . $encoded_payload;
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    public function testValidSignedRequestReturnsPayloadWithUserId(): void
    {
        $sr = $this->buildSignedRequest(['user_id' => '218471'], self::Secret);

        $data = FacebookSignedRequestParser::parse($sr, self::Secret);

        $this->assertIsArray($data);
        $this->assertSame('218471', $data['user_id']);
    }

    public function testLowercaseAlgorithmIsStillAccepted(): void
    {
        $sr = $this->buildSignedRequest(['user_id' => '218471'], self::Secret, 'hmac-sha256');

        $data = FacebookSignedRequestParser::parse($sr, self::Secret);

        $this->assertIsArray($data);
        $this->assertSame('218471', $data['user_id']);
    }

    public function testTamperedPayloadReturnsNull(): void
    {
        $sr = $this->buildSignedRequest(['user_id' => '218471'], self::Secret);
        [$sig, $payload] = explode('.', $sr, 2);

        $tampered_payload = $this->base64UrlEncode(json_encode(['user_id' => '999999', 'algorithm' => 'HMAC-SHA256']));
        $tampered_sr = $sig . '.' . $tampered_payload;

        $this->assertNull(FacebookSignedRequestParser::parse($tampered_sr, self::Secret));
    }

    public function testWrongSecretReturnsNull(): void
    {
        $sr = $this->buildSignedRequest(['user_id' => '218471'], self::Secret);

        $this->assertNull(FacebookSignedRequestParser::parse($sr, 'wrong-secret'));
    }

    public function testUnsupportedAlgorithmReturnsNull(): void
    {
        $sr = $this->buildSignedRequest(['user_id' => '218471'], self::Secret, 'MD5');

        $this->assertNull(FacebookSignedRequestParser::parse($sr, self::Secret));
    }

    public function testMalformedStringWithNoDotReturnsNull(): void
    {
        $this->assertNull(FacebookSignedRequestParser::parse('not-a-valid-signed-request', self::Secret));
    }

    public function testMissingUserIdReturnsNull(): void
    {
        $sr = $this->buildSignedRequest([], self::Secret);

        $this->assertNull(FacebookSignedRequestParser::parse($sr, self::Secret));
    }
}
