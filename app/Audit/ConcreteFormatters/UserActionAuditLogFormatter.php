<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Illuminate\Support\Facades\Log;
use Models\UserAction;

class UserActionAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof UserAction) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $title = $subject->getUserAction() ?? 'Unknown UserAction';
            $owner = $subject->getOwner();
            $realm = $subject->hasRealm() ? $subject->getRealm() : 'N/A';
            $ip = $subject->getFromIp();

            switch ($this->event_type) {

                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("UserAction (%s) for '%s' which owner is \"%s (%s)\", with realm \"%s\" and IP \"%s\" was created by user %s", $id, $title, $owner->getFullName(), $owner->getID(), $realm, $ip, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("UserAction (%s) for '%s' which owner is \"%s (%s)\", with realm \"%s\" and IP \"%s\" was updated: %s by user %s", $id, $title, $owner->getFullName(), $owner->getID(), $realm, $ip, $details, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("UserAction (%s) for '%s' which owner is \"%s (%s)\", with realm \"%s\" and IP \"%s\" was deleted by user %s", $id, $title, $owner->getFullName(), $owner->getID(), $realm, $ip, $this->getUserInfo());
            }
            return "";

        } catch (\Exception $ex) {
            Log::warning("UserAction error: " . $ex->getMessage());
        }

        return null;
    }
}