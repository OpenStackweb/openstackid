<?php

namespace Tests\Jobs;

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

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestAuditContextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $testData;
    public ?int $timeout = null;
    public ?int $tries = null;

    public function __construct(string $testData)
    {
        $this->testData = $testData;
    }

    public function handle(): void
    {
        if (app()->bound('audit.context')) {
            $context = app('audit.context');
            Log::info('TestAuditContextJob processed', [
                'user_id' => $context->userId,
                'user_email' => $context->userEmail,
            ]);
        }
    }

    public function payload(): array
    {
        return [
            'data' => [
                'testData' => $this->testData,
            ],
            'displayName' => self::class,
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'maxTries' => $this->tries,
            'timeout' => $this->timeout,
            'timeoutAt' => null,
        ];
    }

    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function tries(int $tries): self
    {
        $this->tries = $tries;
        return $this;
    }
}
