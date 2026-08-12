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

use Illuminate\Support\Facades\App;
use Tests\BrowserKitTestCase;
use Utils\Services\ICacheService;
use Utils\Services\ILockManagerService;

/**
 * Class LockManagerServiceTest
 * @package Tests\unit
 */
class LockManagerServiceTest extends BrowserKitTestCase
{
    public function testAcquireLockExpiresAfterLifetimeSeconds()
    {
        // acquireLock() must set a RELATIVE Redis TTL of ~lifetime seconds. It used to pass the
        // absolute expiry timestamp (time()+lifetime+1) as the TTL, so a lock never released by
        // its owner (e.g. process killed mid-callback) stayed held for ~55 years instead of
        // auto-recovering after lifetime - permanently blocking every caller of that lock name.
        $lock_manager  = App::make(ILockManagerService::class);
        $cache_service = App::make(ICacheService::class);
        $lock_name     = 'lock.test.relative_ttl';

        try {
            $lock_manager->acquireLock($lock_name, 30);

            $ttl = $cache_service->ttl($lock_name);
            $this->assertGreaterThan(0, $ttl);
            $this->assertLessThanOrEqual(31, $ttl);
        } finally {
            $lock_manager->releaseLock($lock_name);
        }
    }

    public function testLockIsReleasedWhenCallbackThrowsError()
    {
        // lock() must release the lock on ANY Throwable from the callback. It used to catch only
        // Exception, so a PHP Error (e.g. a TypeError inside the guarded transaction) skipped both
        // release paths and left the lock held until the Redis TTL expired.
        $lock_manager = App::make(ILockManagerService::class);
        $lock_name    = 'lock.test.release_on_error';

        try {
            $thrown = false;
            try {
                $lock_manager->lock($lock_name, function () {
                    throw new \TypeError('boom');
                }, 5);
            } catch (\TypeError $ex) {
                $thrown = true;
            }
            $this->assertTrue($thrown);

            // re-acquiring proves the lock was released despite the Error;
            // before the fix this threw UnacquiredLockException
            $lock_manager->acquireLock($lock_name, 5);
            $this->assertGreaterThan(0, App::make(ICacheService::class)->ttl($lock_name));
        } finally {
            $lock_manager->releaseLock($lock_name);
        }
    }
}
