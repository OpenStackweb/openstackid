<?php
/**
 * Child process used by tests/RecoveryCodeConcurrencyTest.php to exercise the
 * per-user locking of recovery-code writes with REAL concurrent connections
 * (the PHPUnit process itself is single-threaded).
 *
 * Usage: php RecoveryCodeWorker.php generate <userId>
 *        php RecoveryCodeWorker.php redeem   <userId> <code>
 *
 * Prints one JSON line: {"ok":true,...} or {"ok":false,"error":"..."}.
 */
use App\Services\Auth\IRecoveryCodeService;
use Auth\User;
use Illuminate\Contracts\Console\Kernel;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Strategies\MFA\MFAChallengeStrategyFactory;
use Utils\Services\IAuthService;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $action, $userId] = $argv;

try {
    /** @var User $user */
    $user = EntityManager::getRepository(User::class)->find((int)$userId);

    if ($action === 'generate') {
        $batch = $app->make(IRecoveryCodeService::class)->generateRecoveryCodes($user);
        echo json_encode(['ok' => true, 'batch_id' => $batch->getBatchId()]), PHP_EOL;
    } elseif ($action === 'redeem') {
        $strategy = MFAChallengeStrategyFactory::create(User::MFAMethod_OTP);
        $app->make(IAuthService::class)->verifyMFARecoveryCode($user, $strategy, $argv[3]);
        echo json_encode(['ok' => true]), PHP_EOL;
    } else {
        echo json_encode(['ok' => false, 'error' => 'unknown action']), PHP_EOL;
    }
} catch (\Throwable $ex) {
    echo json_encode(['ok' => false, 'error' => get_class($ex) . ': ' . $ex->getMessage()]), PHP_EOL;
}
