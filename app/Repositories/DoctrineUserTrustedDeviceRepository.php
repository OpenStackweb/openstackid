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
use App\libs\Auth\Models\UserTrustedDevice;
use Auth\Repositories\IUserTrustedDeviceRepository;
use Auth\User;
use Doctrine\Common\Collections\Criteria;

final class DoctrineUserTrustedDeviceRepository
    extends ModelDoctrineRepository implements IUserTrustedDeviceRepository
{
    protected function getBaseEntity()
    {
        return UserTrustedDevice::class;
    }

    private function buildActiveExpiryExpr(): \Doctrine\Common\Collections\Expr\CompositeExpression
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        return Criteria::expr()->orX(
            Criteria::expr()->gt('expires_at', $now),
            Criteria::expr()->isNull('expires_at')
        );
    }

    public function getActiveByUserAndIdentifier(User $user, string $deviceIdentifier): ?UserTrustedDevice
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user))
            ->andWhere(Criteria::expr()->eq('device_identifier', $deviceIdentifier))
            ->andWhere(Criteria::expr()->eq('is_revoked', false))
            ->andWhere($this->buildActiveExpiryExpr())
            ->setMaxResults(1);

        $result = $this->matching($criteria)->first();
        return $result instanceof UserTrustedDevice ? $result : null;
    }

    public function getActiveByUser(User $user): array
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user))
            ->andWhere(Criteria::expr()->eq('is_revoked', false))
            ->andWhere($this->buildActiveExpiryExpr());

        return $this->matching($criteria)->toArray();
    }
}
