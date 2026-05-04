<?php namespace Strategies\MFA;

use Auth\User;
use Models\OAuth2\Client;

interface IMFAChallengeStrategy
{
    public function issueChallenge(User $user, ?Client $client, bool $remember): array;
    public function verifyChallenge(User $user, string $code): void;
    public function resendChallenge(User $user, ?Client $client, bool $remember): array;
    public function getPendingState(): ?array;
    public function clearPendingState(): void;
    public function verifyRecoveryCode(User $user, string $code): void;
}
