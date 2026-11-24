<?php

namespace App\Services\Auth;

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
 * A freshly generated batch of recovery codes together with its identifier.
 *
 * The identifier is read inside the same locked transaction that deletes and
 * inserts the rows (SDS idp-mfa.md §3.5): a MIN(id) read after the commit can
 * belong to a concurrent generation that replaced the batch in between, which
 * would pair the dead codes of one batch with the identifier of another.
 *
 * @package App\Services\Auth
 */
final class RecoveryCodesBatch
{
    /**
     * @param string[] $codes plaintext codes formatted as XXXX-XXXX
     * @param int $batchId smallest user_recovery_codes.id of the batch
     */
    public function __construct(
        private readonly array $codes,
        private readonly int $batchId,
    ) {
    }

    /**
     * @return string[]
     */
    public function getCodes(): array
    {
        return $this->codes;
    }

    public function getBatchId(): int
    {
        return $this->batchId;
    }
}
