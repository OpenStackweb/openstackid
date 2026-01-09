<?php
namespace App\Audit;

/**
 * Copyright 2022 OpenStack Foundation
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

use App\Audit\ConcreteFormatters\ChildEntityFormatters\ChildEntityFormatterFactory;
use App\Audit\ConcreteFormatters\EntityCollectionUpdateAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityCreationAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityDeletionAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityUpdateAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Facades\Log;
use models\utils\IEntity;

/**
 * Class AuditLogFormatterStrategy
 * @package App\Audit
 */
class AuditLogStrategy implements IAuditStrategy
{
    public const EVENT_COLLECTION_UPDATE = 'event_collection_update';
    public const EVENT_ENTITY_CREATION = 'event_entity_creation';
    public const EVENT_ENTITY_DELETION = 'event_entity_deletion';
    public const EVENT_ENTITY_UPDATE = 'event_entity_update';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function resolveAuditableEntity($subject): ?IEntity
    {

        return null;
    }

    /**
     * @param $subject
     * @param $change_set
     * @param $event_type
     * @return void
     */
    public function audit($subject, $change_set, $event_type, AuditContext $ctx): void
    {
        try {
            $entity = $this->resolveAuditableEntity($subject);

            if (!($entity instanceof IEntity))
                return;

            $logger = AuditLoggerFactory::build($entity);
            if (is_null($logger))
                return;

            $formatter = null;

            switch ($event_type) {
                case self::EVENT_COLLECTION_UPDATE:
                    $child_entity = null;
                    if (count($subject) > 0) {
                        $child_entity = $subject[0];
                    }
                    if (is_null($child_entity) && count($subject->getSnapshot()) > 0) {
                        $child_entity = $subject->getSnapshot()[0];
                    }
                    $child_entity_formatter = $child_entity != null ? ChildEntityFormatterFactory::build($child_entity) : null;
                    $formatter = new EntityCollectionUpdateAuditLogFormatter($child_entity_formatter);
                    break;
                case self::EVENT_ENTITY_CREATION:
                    $formatter = new EntityCreationAuditLogFormatter();
                    break;
                case self::EVENT_ENTITY_DELETION:
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityDeletionAuditLogFormatter($child_entity_formatter);
                    break;
                case self::EVENT_ENTITY_UPDATE:
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityUpdateAuditLogFormatter($child_entity_formatter);
                    break;
            }

            if (is_null($formatter))
                return;

            $description = $formatter->format($subject, $change_set);

            if (!empty($description)) {
                $logger->createAuditLogEntry($this->em, $entity, $description);
            }
        } catch (\Exception $ex) {
            Log::warning($ex);
        }
    }
}