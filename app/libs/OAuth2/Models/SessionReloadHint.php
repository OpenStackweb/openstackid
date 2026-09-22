<?php namespace OAuth2\Models;
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

/**
 * Class SessionReloadHint
 *
 * What an id_token_hint authorizes AuthService::reloadSession() to do.
 *
 * - jtiOnly(): the hint's signature was verified with a client-controlled key
 *   (HS* client secret, a public key the client registered, its jwks_uri), so it
 *   only proves the client made it. The session may be resumed through the
 *   cached jti, nothing else.
 * - withSubFallback(): the signature was verified with this IDP's own signing
 *   key AND the hint carries an attested authentication time, so when the
 *   cached session can't be resumed the user it names may be logged in with
 *   that auth_time.
 *
 * The named constructors make "user_id without auth_time" (or the reverse)
 * unrepresentable.
 *
 * @package OAuth2\Models
 */
final class SessionReloadHint
{
    /**
     * @var string
     */
    private $jti;

    /**
     * @var int|null
     */
    private $user_id;

    /**
     * @var int|null
     */
    private $auth_time;

    /**
     * @param string $jti
     * @param int|null $user_id
     * @param int|null $auth_time
     */
    private function __construct(string $jti, ?int $user_id, ?int $auth_time)
    {
        $this->jti       = $jti;
        $this->user_id   = $user_id;
        $this->auth_time = $auth_time;
    }

    /**
     * Hint verified with a client-controlled key: jti-only semantics.
     * @param string $jti
     * @return SessionReloadHint
     */
    public static function jtiOnly(string $jti): self
    {
        return new self($jti, null, null);
    }

    /**
     * Hint verified with the IDP's own signing key and carrying an attested
     * authentication time (its auth_time claim, else iat).
     * @param string $jti
     * @param int $user_id
     * @param int $auth_time epoch the IDP originally attested for that user
     * @return SessionReloadHint
     */
    public static function withSubFallback(string $jti, int $user_id, int $auth_time): self
    {
        return new self($jti, $user_id, $auth_time);
    }

    /**
     * @return string
     */
    public function getJti(): string
    {
        return $this->jti;
    }

    /**
     * @return bool
     */
    public function allowsSubFallback(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * @return int|null
     */
    public function getAuthTime(): ?int
    {
        return $this->auth_time;
    }
}
