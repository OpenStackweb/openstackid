<?php

namespace App\Audit\ConcreteFormatters\ChildEntityFormatters;

use Models\UserAction;

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


/**
 * Class UserActionAuditLogFormatter
 * @package App\Audit\ConcreteFormatters
 */
class UserActionAuditLogFormatter implements IChildEntityAuditLogFormatter
{
  /**
   * @param UserAction $subject
   * @param string $child_entity_action_type
   * @param string|null $additional_info
   * @return string|null
   */
  public function format($subject, string $child_entity_action_type, ?string $additional_info = ""): ?string
  {

    if (!$subject instanceof UserAction) {
      return null;
    }

    $owner = $subject->getOwner();
    $realm = $subject->hasRealm() ? $subject->getRealm() : 'N/A';
    $ip = $subject->getFromIp();

    switch ($child_entity_action_type) {
      case IChildEntityAuditLogFormatter::CHILD_ENTITY_CREATION:
        return "A new UserAction which owner is \"{$owner->getFullName()} ({$owner->getID()})\", with realm \"{$realm}\" and IP \"{$ip}\" was created. {$additional_info}";
      case IChildEntityAuditLogFormatter::CHILD_ENTITY_UPDATE:
        return "An UserAction with ID {$subject->getID()} was changed. {$additional_info}";
      case IChildEntityAuditLogFormatter::CHILD_ENTITY_DELETION:
        return "An UserAction with ID {$subject->getID()} which owner is \"{$owner->getFullName()} ({$owner->getID()})\", with realm \"{$realm}\" and IP \"{$ip}\" was removed";
    }
    return "";
  }
}