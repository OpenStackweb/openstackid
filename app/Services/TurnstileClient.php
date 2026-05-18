<?php
namespace App\Services;
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

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RyanChandler\LaravelCloudflareTurnstile\Contracts\ClientInterface;
use RyanChandler\LaravelCloudflareTurnstile\Responses\SiteverifyResponse;

/**
 * Replaces the vendor Client (v3.0.3) whose siteverify() has inverted
 * success/failure logic: it returns success() on HTTP non-2xx and failure()
 * on HTTP 2xx, causing a TypeError when Cloudflare returns {success:true}.
 */
final class TurnstileClient implements ClientInterface
{
    public function __construct(private string $secret)
    {
    }

    public function siteverify(string $token): SiteverifyResponse
    {
        $response = Http::timeout(5)
            ->retry(3, 100, fn($e) => $e instanceof ConnectionException)
            ->asForm()
            ->acceptJson()
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => request()->ip(),
                'idempotency_key' => uniqid('', true),
            ]);

        if (!$response->ok()) {
            return SiteverifyResponse::failure(['http-error']);
        }

        if ($response->json('success') === true) {
            return SiteverifyResponse::success();
        }

        return SiteverifyResponse::failure($response->json('error-codes') ?: ['internal-error']);
    }

    public function dummy(): string
    {
        return self::RESPONSE_DUMMY_TOKEN;
    }
}