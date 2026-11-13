<?php namespace Tests;
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

use App\libs\Auth\Factories\UserFactory;
use App\libs\Auth\Models\TwoFactorAuditLog;
use App\libs\Auth\Models\UserTrustedDevice;
use App\Services\Auth\IDeviceTrustService;
use Auth\AuthHelper;
use Auth\User;
use DateInterval;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Integration tests for the trusted-devices management endpoints under
 * admin/api/v1/users/me/2fa/devices (Api\UserApiController).
 *
 * @package Tests
 */
final class TrustedDevicesApiTest extends OpenStackIDBaseTestCase
{
    // Seeded super-admin (member of an enforced 2FA group, email verified, email_otp).
    private const ADMIN_EMAIL   = 'sebastian@tipit.net';
    private const SEED_PASSWORD = '1Qaz2wsx!';

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        Session::start();
    }

    // -------------------------------------------------------------------------
    // GET /2fa/devices
    // -------------------------------------------------------------------------

    public function testListReturnsOnlyCallersActiveDevices(): void
    {
        $admin = $this->admin();
        $activeToken = $this->trustDevice($admin);
        $this->trustDevice($admin);
        $revokedId = $this->deviceIdFor($this->trustDevice($admin));
        $this->markRevoked($revokedId);
        $expiredId = $this->deviceIdFor($this->trustDevice($admin));
        $this->markExpired($expiredId);

        $other = $this->user($this->createPlainUser());
        $otherId = $this->deviceIdFor($this->trustDevice($other));

        $this->be($this->admin());
        $response = $this->listDevices([Config::get('two_factor.cookie_name') => $activeToken]);

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertCount(2, $payload['data'], 'only the caller\'s non-revoked, non-expired devices must be listed');

        $ids = array_column($payload['data'], 'id');
        $this->assertNotContains($revokedId, $ids);
        $this->assertNotContains($expiredId, $ids);
        $this->assertNotContains($otherId, $ids, 'another user\'s devices must never be listed');

        foreach ($payload['data'] as $row) {
            $this->assertArrayNotHasKey('device_identifier', $row);
            $this->assertArrayNotHasKey('user_agent', $row);
            foreach (['id', 'device_name', 'ip_address', 'trusted_at', 'expires_at', 'last_seen_at', 'is_current'] as $key) {
                $this->assertArrayHasKey($key, $row);
            }
        }

        $current = array_values(array_filter($payload['data'], fn($row) => $row['is_current']));
        $this->assertCount(1, $current, 'exactly the device matching the request cookie must be flagged as current');
        $this->assertSame($this->deviceIdFor($activeToken), $current[0]['id']);
    }

    public function testListWithoutCookieFlagsNoDeviceAsCurrent(): void
    {
        $admin = $this->admin();
        $this->trustDevice($admin);

        $this->be($admin);
        $response = $this->listDevices();

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertCount(1, $payload['data']);
        $this->assertFalse($payload['data'][0]['is_current']);
    }

    public function testListDoesNotTouchLastSeenAt(): void
    {
        $admin = $this->admin();
        $token = $this->trustDevice($admin);
        $id = $this->deviceIdFor($token);
        $before = $this->device($id)->getLastSeenAt()->getTimestamp();

        sleep(1);
        $this->be($admin);
        $this->listDevices([Config::get('two_factor.cookie_name') => $token]);
        $this->assertResponseStatus(200);

        $this->assertSame($before, $this->device($id)->getLastSeenAt()->getTimestamp(), 'listing must not refresh last_seen_at');
    }

    // -------------------------------------------------------------------------
    // DELETE /2fa/devices/{id}
    // -------------------------------------------------------------------------

    public function testRevokeDeviceMarksRowAndLogsOneAuditEvent(): void
    {
        $admin = $this->admin();
        $id = $this->deviceIdFor($this->trustDevice($admin));
        $auditBefore = $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked);

        $this->be($this->admin());
        $this->revokeDevice($id);

        $this->assertResponseStatus(204);
        $this->assertTrue($this->device($id)->isRevoked());
        $this->assertSame($auditBefore + 1, $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked));
    }

    public function testRevokeAlreadyRevokedDeviceIsIdempotentAndNotAudited(): void
    {
        $admin = $this->admin();
        $id = $this->deviceIdFor($this->trustDevice($admin));

        $this->be($this->admin());
        $this->revokeDevice($id);
        $this->assertResponseStatus(204);
        $auditAfterFirst = $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked);

        $this->be($this->admin());
        $this->revokeDevice($id);

        $this->assertResponseStatus(204);
        $this->assertSame($auditAfterFirst, $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked), 'a no-op revoke must not be audited');
    }

    public function testRevokeExpiredDeviceIsIdempotentAndNotAudited(): void
    {
        $admin = $this->admin();
        $id = $this->deviceIdFor($this->trustDevice($admin));
        $this->markExpired($id);
        $auditBefore = $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked);

        $this->be($this->admin());
        $this->revokeDevice($id);

        $this->assertResponseStatus(204);
        $this->assertSame($auditBefore, $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked));
    }

    public function testRevokeAnotherUsersDeviceReturns404AndLeavesRowUnchanged(): void
    {
        $other = $this->user($this->createPlainUser());
        $otherId = $this->deviceIdFor($this->trustDevice($other));

        $this->be($this->admin());
        $this->revokeDevice($otherId);

        $this->assertResponseStatus(404);
        $this->assertFalse($this->device($otherId)->isRevoked(), 'another user\'s device must not be revoked');
        $this->assertSame(0, $this->countAudit($other->getId(), TwoFactorAuditLog::EventDeviceRevoked));
    }

    public function testRevokeUnknownDeviceReturns404(): void
    {
        $this->be($this->admin());
        $this->revokeDevice(PHP_INT_MAX);

        $this->assertResponseStatus(404);
    }

    public function testRevokingCurrentDeviceExpiresCookieAndNextLoginIsChallenged(): void
    {
        $admin = $this->admin();
        $token = $this->trustDevice($admin);
        $id = $this->deviceIdFor($token);

        $this->be($this->admin());
        $response = $this->revokeDevice($id, [Config::get('two_factor.cookie_name') => $token]);

        $this->assertResponseStatus(204);
        $cookie = $this->deviceTrustCookie($response);
        $this->assertNotNull($cookie, 'revoking the current device must send back the device-trust cookie');
        $this->assertTrue($cookie->isCleared(), 'the device-trust cookie must be expired');

        // Even if the browser kept the old token, the server-side row is revoked.
        Auth::logout();
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD, [Config::get('two_factor.cookie_name') => $token]);

        $this->assertResponseStatus(302);
        $this->assertFalse(Auth::check(), 'a revoked device must no longer bypass the challenge');
        $this->assertSame('2fa', Session::get('flow'), 'the next login must land on the 2FA challenge');
    }

    public function testRevokingAnotherDeviceKeepsCurrentCookie(): void
    {
        $admin = $this->admin();
        $currentToken = $this->trustDevice($admin);
        $otherId = $this->deviceIdFor($this->trustDevice($admin));

        $this->be($this->admin());
        $response = $this->revokeDevice($otherId, [Config::get('two_factor.cookie_name') => $currentToken]);

        $this->assertResponseStatus(204);
        $this->assertNull($this->deviceTrustCookie($response), 'the current device\'s cookie must be left alone');
        $this->assertFalse($this->device($this->deviceIdFor($currentToken))->isRevoked());
    }

    // -------------------------------------------------------------------------
    // DELETE /2fa/devices
    // -------------------------------------------------------------------------

    public function testRevokeAllRevokesEveryActiveDeviceAndLogsOneEventPerDevice(): void
    {
        $admin = $this->admin();
        $token = $this->trustDevice($admin);
        $ids = [$this->deviceIdFor($token), $this->deviceIdFor($this->trustDevice($admin)), $this->deviceIdFor($this->trustDevice($admin))];
        $alreadyRevoked = $this->deviceIdFor($this->trustDevice($admin));
        $this->markRevoked($alreadyRevoked);

        $other = $this->user($this->createPlainUser());
        $otherId = $this->deviceIdFor($this->trustDevice($other));

        $auditBefore = $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked);

        $this->be($this->admin());
        $response = $this->revokeAllDevices([Config::get('two_factor.cookie_name') => $token]);

        $this->assertResponseStatus(204);
        foreach ($ids as $id) {
            $this->assertTrue($this->device($id)->isRevoked());
        }
        $this->assertSame(
            $auditBefore + count($ids),
            $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked),
            'exactly one audit event per device actually revoked'
        );
        $this->assertFalse($this->device($otherId)->isRevoked(), 'another user\'s devices must not be touched');

        $cookie = $this->deviceTrustCookie($response);
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isCleared());
    }

    public function testRevokeAllWithoutActiveDevicesLogsNothing(): void
    {
        $admin = $this->admin();
        $auditBefore = $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked);

        $this->be($admin);
        $response = $this->revokeAllDevices();

        $this->assertResponseStatus(204);
        $this->assertSame($auditBefore, $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceRevoked));
        $this->assertNull($this->deviceTrustCookie($response));
    }

    // -------------------------------------------------------------------------
    // unauthenticated
    // -------------------------------------------------------------------------

    /**
     * @dataProvider deviceRoutes
     */
    public function testUnauthenticatedCallIsRedirectedToLogin(string $method, string $uri): void
    {
        $admin = $this->admin();
        $id = $this->deviceIdFor($this->trustDevice($admin));

        // Sent over HTTPS so the ssl middleware lets it through and the auth
        // middleware is the one being exercised.
        $response = $this->call($method, 'https://localhost' . str_replace('{id}', (string)$id, $uri));

        // The admin/api/v1 group's auth middleware rejects guests before the
        // controller runs, same as the sibling 2fa/enable route.
        $this->assertResponseStatus(302);
        $this->assertStringContainsString('/auth/login', $response->headers->get('Location'));
        $this->assertFalse($this->device($id)->isRevoked());
    }

    public static function deviceRoutes(): array
    {
        return [
            'list'       => ['GET', '/admin/api/v1/users/me/2fa/devices'],
            'revoke one' => ['DELETE', '/admin/api/v1/users/me/2fa/devices/{id}'],
            'revoke all' => ['DELETE', '/admin/api/v1/users/me/2fa/devices'],
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function listDevices(array $cookies = [])
    {
        return $this->action('GET', 'Api\\UserApiController@getMyTrustedDevices', [], [], $cookies);
    }

    private function revokeDevice(int $id, array $cookies = [])
    {
        return $this->action('DELETE', 'Api\\UserApiController@revokeMyTrustedDevice', ['id' => $id], [], $cookies);
    }

    private function revokeAllDevices(array $cookies = [])
    {
        return $this->action('DELETE', 'Api\\UserApiController@revokeAllMyTrustedDevices', [], [], $cookies);
    }

    private function postLogin(string $username, string $password, array $cookies = [])
    {
        return $this->action('POST', 'UserController@postLogin', [
            'username' => $username,
            'password' => $password,
            'flow'     => 'password',
            '_token'   => Session::token(),
        ], [], $cookies);
    }

    private function trustDevice(User $user): string
    {
        // Helpers below clear the entity manager, so re-attach the owner first.
        $user = EntityManager::getRepository(User::class)->find($user->getId());
        /** @var IDeviceTrustService $service */
        $service = App::make(IDeviceTrustService::class);
        return $service->trustDevice($user, 'Mozilla/5.0 (test)', '127.0.0.1');
    }

    private function deviceIdFor(string $rawToken): int
    {
        EntityManager::clear();
        $device = EntityManager::getRepository(UserTrustedDevice::class)
            ->findOneBy(['device_identifier' => hash('sha256', $rawToken)]);
        $this->assertInstanceOf(UserTrustedDevice::class, $device);
        return $device->getId();
    }

    private function device(int $id): UserTrustedDevice
    {
        EntityManager::clear();
        $device = EntityManager::getRepository(UserTrustedDevice::class)->find($id);
        $this->assertInstanceOf(UserTrustedDevice::class, $device);
        return $device;
    }

    private function markRevoked(int $id): void
    {
        $device = $this->device($id);
        $device->setIsRevoked(true);
        EntityManager::flush();
    }

    private function markExpired(int $id): void
    {
        $device = $this->device($id);
        $past = new DateTime('now', new DateTimeZone('UTC'));
        $past->sub(new DateInterval('P1D'));
        $device->setExpiresAt($past);
        EntityManager::flush();
    }

    private function deviceTrustCookie($response): ?Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === Config::get('two_factor.cookie_name')) {
                return $cookie;
            }
        }
        return null;
    }

    private function admin(): User
    {
        return $this->user(self::ADMIN_EMAIL);
    }

    private function user(string $email): User
    {
        EntityManager::clear();
        $user = EntityManager::getRepository(User::class)->getByEmailOrName($email);
        $this->assertInstanceOf(User::class, $user, "user {$email} not found");
        return $user;
    }

    private function createPlainUser(): string
    {
        $email = 'plain.' . uniqid() . '@test.invalid';
        $user = UserFactory::build([
            'first_name'     => 'Plain',
            'last_name'      => 'User',
            'email'          => $email,
            'password'       => self::SEED_PASSWORD,
            'password_enc'   => AuthHelper::AlgSHA1_V2_4,
            'active'         => true,
            'email_verified' => true,
            'identifier'     => 'plain.' . uniqid(),
        ]);
        EntityManager::persist($user);
        EntityManager::flush();
        return $email;
    }

    private function countAudit(int $userId, string $eventType): int
    {
        EntityManager::clear();
        return count(
            EntityManager::getRepository(TwoFactorAuditLog::class)
                ->findBy(['user' => $userId, 'event_type' => $eventType])
        );
    }
}
