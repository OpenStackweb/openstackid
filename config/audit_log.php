<?php

return [
  'entities' => [
    \Models\UserAction::class => [
      'enabled' => true,
      'strategy' => App\Audit\ConcreteFormatters\UserActionAuditLogFormatter::class
    ],
  ]
];