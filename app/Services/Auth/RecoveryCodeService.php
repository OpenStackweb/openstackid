<?php
namespace App\Services\Auth;
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

use App\libs\Auth\Models\TwoFactorAuditLog;
use App\libs\Auth\Models\UserRecoveryCode;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;
use Illuminate\Support\Facades\Hash;
use Laminas\Math\Rand;
use models\exceptions\ValidationException;
use Utils\Db\ITransactionService;
use Utils\IPHelper;

/**
 * Class RecoveryCodeService
 * @package App\Services\Auth
 */
final class RecoveryCodeService implements IRecoveryCodeService
{
    private const CODE_CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public function __construct(
        private readonly IUserRecoveryCodeRepository $repository,
        private readonly ITransactionService $tx_service,
        private readonly ITwoFactorAuditService $audit_service,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function regenerateRecoveryCodes(User $user, string $currentPassword): array
    {
        if (!$user->checkPassword(trim($currentPassword))) {
            throw new ValidationException('current_password is not correct.');
        }

        return $this->generateRecoveryCodes($user);
    }

    /**
     * @inheritDoc
     */
    public function generateRecoveryCodes(User $user): array
    {
        $count = (int)config('auth.recovery_codes.count', 10);
        $length = (int)config('auth.recovery_codes.length', 8);

        $plaintext_codes = [];

        $this->tx_service->transaction(function () use ($user, $count, $length, &$plaintext_codes) {
            $this->repository->deleteAllForUser($user);

            for ($i = 0; $i < $count; $i++) {
                $plain = Rand::getString($length, self::CODE_CHARSET, true);
                $plaintext_codes[] = $plain;

                $code = new UserRecoveryCode();
                $code->setUser($user);
                $code->setCodeHash(Hash::make($plain));
                $this->repository->add($code, false);
            }
        });

        $this->audit_service->log(
            $user,
            TwoFactorAuditLog::EventRecoveryCodesGenerated,
            TwoFactorAuditLog::MethodRecovery,
            IPHelper::getUserIp()
        );

        return array_map(static fn(string $code) => implode('-', str_split($code, 4)), $plaintext_codes);
    }

    /**
     * @inheritDoc
     */
    public function countUnusedRecoveryCodes(User $user): int
    {
        return count($this->repository->getUnusedByUser($user));
    }
}
