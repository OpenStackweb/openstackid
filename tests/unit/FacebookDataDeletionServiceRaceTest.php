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
use App\Services\Auth\FacebookDataDeletionService;
use Auth\Repositories\IUserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use LaravelDoctrine\ORM\Facades\Registry;
use Tests\TestCase;
use Utils\Db\ITransactionService;

/**
 * Class FacebookDataDeletionServiceRaceTest
 * Exercises the unique-constraint-collision (upsert) path of
 * FacebookDataDeletionService::processDeletionRequest() deterministically,
 * simulating two concurrent requests for the same (provider, external_id)
 * racing the audit table's unique index.
 * @package Tests\unit
 */
final class FacebookDataDeletionServiceRaceTest extends TestCase
{
    public function testConcurrentInsertCollisionReturnsExistingRowInsteadOfThrowing(): void
    {
        $user_repository = $this->createMock(IUserRepository::class);
        $user_repository->method('getByExternalId')->willReturn(null);

        $connection = $this->createMock(Connection::class);

        $lookup_calls = 0;
        $connection->method('fetchAssociative')
            ->willReturnCallback(function () use (&$lookup_calls) {
                $lookup_calls++;
                if ($lookup_calls === 1) {
                    // first check: no row yet, so processDeletionRequest proceeds to insert
                    return false;
                }
                // second check (inside the catch): a concurrent writer already
                // committed the row for this (provider, external_id) pair
                return [
                    'provider' => 'facebook',
                    'external_id' => 'racing-asid',
                    'confirmation_code' => 'winner-confirmation-code',
                    'status' => 'not_found',
                    'user_id' => null,
                ];
            });

        $connection->method('insert')
            ->willThrowException($this->createMock(UniqueConstraintViolationException::class));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($em);
        Registry::swap($registry);

        $tx_service = $this->createMock(ITransactionService::class);
        $tx_service->method('transaction')->willReturnCallback(fn($callback) => $callback());

        $service = new FacebookDataDeletionService($user_repository, $tx_service);

        $result = $service->processDeletionRequest('racing-asid');

        $this->assertSame('winner-confirmation-code', $result['confirmation_code']);
        $this->assertSame('not_found', $result['status']);
    }
}
