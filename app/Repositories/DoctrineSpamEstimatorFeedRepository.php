<?php namespace App\Repositories;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\libs\Auth\Models\SpamEstimatorFeed;
use App\libs\Auth\Repositories\ISpamEstimatorFeedRepository;
use Illuminate\Support\Facades\Log;
/**
 * Class DoctrineSpamEstimatorFeedRepository
 * @package App\Repositories
 */
final class DoctrineSpamEstimatorFeedRepository
    extends ModelDoctrineRepository implements ISpamEstimatorFeedRepository
{

    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return SpamEstimatorFeed::class;
    }

    public function deleteByEmail(string $email)
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb->delete(SpamEstimatorFeed::class, 'e');
            $qb->where('e.email = :email');
            $qb->setParameter('email', trim($email));
            $qb->getQuery()->execute();
        }
        catch(\Exception $ex){
            Log::error($ex);
        }
    }
}