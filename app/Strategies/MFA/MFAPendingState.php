<?php namespace Strategies\MFA;
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
 * Immutable snapshot of a pending (issued, not yet verified) MFA challenge,
 * as read back from the session by IMFAChallengeStrategy::getPendingState().
 *
 * @package Strategies\MFA
 */
final class MFAPendingState
{
    public function __construct(
        private readonly int  $user_id,
        private readonly int  $pending_at,
        private readonly bool $remember,
    ) {}

    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * Unix timestamp of when the challenge was issued.
     */
    public function getPendingAt(): int
    {
        return $this->pending_at;
    }

    /**
     * Whether the original login submission asked for a remembered session.
     */
    public function shouldRemember(): bool
    {
        return $this->remember;
    }
}
