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

use Utils\Db\ITransactionService;

/**
 * Class DoctrineTransactionAfterCommitTest
 * @package Tests
 */
final class DoctrineTransactionAfterCommitTest extends OpenStackIDBaseTestCase
{
    private function txService(): ITransactionService
    {
        return $this->app->make(ITransactionService::class);
    }

    public function testRunsImmediatelyWhenNoTransactionIsActive(): void
    {
        $ran = false;
        $this->txService()->afterCommit(function () use (&$ran) { $ran = true; });
        $this->assertTrue($ran);
    }

    public function testRunsAfterTheOutermostTransactionCommits(): void
    {
        $tx     = $this->txService();
        $events = [];

        $tx->transaction(function () use ($tx, &$events) {
            $tx->afterCommit(function () use (&$events) { $events[] = 'callback'; });
            $tx->transaction(function () use (&$events) { $events[] = 'inner'; });
            $events[] = 'outer';
        });

        $this->assertEquals(['inner', 'outer', 'callback'], $events);
    }

    public function testIsDiscardedWhenTheTransactionFails(): void
    {
        $tx  = $this->txService();
        $ran = false;

        try {
            $tx->transaction(function () use ($tx, &$ran) {
                $tx->afterCommit(function () use (&$ran) { $ran = true; });
                throw new \RuntimeException('boom');
            });
            $this->fail('the exception must be rethrown');
        } catch (\RuntimeException $ex) {
            $this->assertEquals('boom', $ex->getMessage());
        }

        $this->assertFalse($ran);
    }

    /**
     * A callback that runs a transaction of its own and fails clears the shared queue,
     * the other callbacks of the committed batch must still run.
     */
    public function testFailingCallbackTransactionDoesNotDiscardSiblingCallbacks(): void
    {
        $tx  = $this->txService();
        $ran = [];

        $tx->transaction(function () use ($tx, &$ran) {
            $tx->afterCommit(function () use ($tx) {
                $tx->transaction(function () {
                    throw new \RuntimeException('inner transaction fails');
                });
            });
            $tx->afterCommit(function () use (&$ran) { $ran[] = 'second'; });
            $tx->afterCommit(function () use (&$ran) { $ran[] = 'third'; });
        });

        $this->assertEquals(['second', 'third'], $ran);
    }
}
