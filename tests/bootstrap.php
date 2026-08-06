<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap/autoload.php';

// PHPUnit runs from the CLI, where REMOTE_ADDR is unset. Code under test reads it directly
// (UserIPHelperProvider), so without a deterministic value here the resource-server IP checks
// depended on whichever earlier test happened to set $_SERVER['REMOTE_ADDR'] and leak it -
// the same suite passed or failed depending on test order.
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';


use Symfony\Component\ErrorHandler\ErrorHandler;

//ErrorHandler::register(null, false);