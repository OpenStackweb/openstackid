<?php namespace App\Services\Auth;
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
 * Interface IFacebookDataDeletionService
 * @package App\Services\Auth
 */
interface IFacebookDataDeletionService
{
    /**
     * Idempotently unlinks the Facebook identity from the matching user (if any)
     * and returns a confirmation code + status URL, per Facebook's Data Deletion
     * Request Callback contract.
     *
     * @param string $external_id
     * @param string $provider
     * @return array{confirmation_code: string, status: string, url: string}
     */
    public function processDeletionRequest(string $external_id, string $provider = 'facebook'): array;

    /**
     * @param string $confirmation_code
     * @return array|null
     */
    public function getStatus(string $confirmation_code): ?array;
}
