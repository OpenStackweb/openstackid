<?php namespace App\Repositories;
/**
 * Copyright 2019 OpenStack Foundation
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
use Models\OpenId\ServerExtension;
/**
 * Class DoctrineServerExtensionRepository
 * @package App\Repositories
 */
class DoctrineServerExtensionRepository extends ModelDoctrineRepository
implements IServerExtensionRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return ServerExtension::class;
    }

    /**
     * @return ServerExtension[]
     */
    public function getAllActives(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.active = 1")
            ->getQuery()->execute();
    }
}