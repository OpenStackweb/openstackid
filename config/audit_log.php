<?php

use App\Audit\ConcreteFormatters\UserActionAuditLogFormatter;
use Models\UserAction;

return [
  'entities' => [
    UserAction::class => [
      'enabled' => true,
      'strategy' => UserActionAuditLogFormatter::class,
    ],
  ]
];