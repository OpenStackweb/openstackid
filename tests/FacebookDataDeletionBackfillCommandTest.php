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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class FacebookDataDeletionBackfillCommandTest
 * @package Tests
 */
final class FacebookDataDeletionBackfillCommandTest extends BrowserKitTestCase
{
    const Command = 'idp:facebook-data-deletion-backfill';

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        DB::table('facebook_deletion_requests')->delete();
    }

    private function linkSeededUserToFacebook(string $asid): User
    {
        $user_repository = EntityManager::getRepository(User::class);
        $user = $user_repository->findOneBy(["identifier" => 'sebastian.marcet']);
        $user->setExternalId($asid);
        $user->setExternalProvider('facebook');
        EntityManager::persist($user);
        EntityManager::flush();
        return $user;
    }

    private function writeCsvFixture(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fb_deletion_csv_');
        file_put_contents($path, implode("\n", $lines) . "\n");
        return $path;
    }

    public function testBackfillUnlinksMatchedAndRecordsUnmatched(): void
    {
        $matched_asid = '555000111';
        $unmatched_asid = '555000222';
        $user = $this->linkSeededUserToFacebook($matched_asid);

        $csv_path = $this->writeCsvFixture([$matched_asid, $unmatched_asid, '']);

        $exit_code = Artisan::call(self::Command, ['path' => $csv_path]);

        $this->assertSame(0, $exit_code);

        $reloaded = EntityManager::getRepository(User::class)->getById($user->getId());
        $this->assertNull($reloaded->getExternalId());

        $matched_row = DB::table('facebook_deletion_requests')->where('external_id', $matched_asid)->first();
        $this->assertSame('completed', $matched_row->status);

        $unmatched_row = DB::table('facebook_deletion_requests')->where('external_id', $unmatched_asid)->first();
        $this->assertSame('not_found', $unmatched_row->status);

        unlink($csv_path);
    }

    public function testBackfillIsIdempotentAcrossRuns(): void
    {
        $asid = '555000333';
        $csv_path = $this->writeCsvFixture([$asid]);

        Artisan::call(self::Command, ['path' => $csv_path]);
        Artisan::call(self::Command, ['path' => $csv_path]);

        $count = DB::table('facebook_deletion_requests')->where('external_id', $asid)->count();
        $this->assertSame(1, $count);

        unlink($csv_path);
    }

    public function testUnreadablePathReturnsNonZeroExitCode(): void
    {
        $exit_code = Artisan::call(self::Command, ['path' => '/no/such/file.csv']);

        $this->assertNotSame(0, $exit_code);
    }
}
