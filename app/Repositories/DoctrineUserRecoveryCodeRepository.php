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

    public function refreshExclusiveLock(UserRecoveryCode $code): void
    {
        // Single round-trip: SELECT ... FOR UPDATE that also re-hydrates the entity.
        $this->getEntityManager()->refresh($code, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
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
}
