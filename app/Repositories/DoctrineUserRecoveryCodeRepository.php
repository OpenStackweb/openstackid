<?php namespace App\Repositories;
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
use App\libs\Auth\Models\UserRecoveryCode;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;

final class DoctrineUserRecoveryCodeRepository
    extends ModelDoctrineRepository implements IUserRecoveryCodeRepository
{
    protected function getBaseEntity()
    {
        return UserRecoveryCode::class;
    }

    public function getUnusedByUser(User $user): array
    {
        return $this->findBy([
            'user'    => $user,
            'used_at' => null,
        ]);
    }

    public function refreshExclusiveLock(UserRecoveryCode $code): bool
    {
        // Single round-trip: SELECT ... FOR UPDATE that also re-hydrates the
        // managed entity (HINT_REFRESH). EntityManager::refresh() is not used
        // because it hydrates nothing and reports nothing when the row is gone,
        // which would leave a deleted code looking unused.
        $locked = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from(UserRecoveryCode::class, 'c')
            ->where('c.id = :id')
            ->setParameter('id', $code->getId())
            ->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();

        return $locked !== null;
    }

    public function deleteAllForUser(User $user): int
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder()
            ->delete(UserRecoveryCode::class, 'c')
            ->where('c.user = :user')
            ->setParameter('user', $user);
        return (int) $qb->getQuery()->execute();
    }

    public function lockUserRow(User $user): void
    {
        // SELECT ... FOR UPDATE with HINT_REFRESH: re-hydrates the managed User
        // from the locked row. EntityManager::lock() alone takes the lock but
        // leaves the in-memory state untouched, so a value changed by a
        // concurrent transaction (recovery_codes_acknowledged_at) would be stale.
        $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $user->getId())
            ->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    public function getCurrentBatchId(User $user): ?int
    {
        $em = $this->getEntityManager();
        $em->flush();

        $id = $em->createQueryBuilder()
            ->select('MIN(c.id)')
            ->from(UserRecoveryCode::class, 'c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return is_null($id) ? null : (int) $id;
    }
}
