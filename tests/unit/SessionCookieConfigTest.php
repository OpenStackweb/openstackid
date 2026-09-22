<?php
namespace Tests\unit;
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

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;

/**
 * Regression: the session cookie defaults were flipped to secure=false /
 * same_site=lax, so any environment that does not set the env vars (prod sets
 * neither SESSION_SECURE_COOKIE nor SESSION_COOKIE_SAME_SITE) silently lost the
 * Secure flag and the SameSite=None the OAuth2 memento needs to survive
 * cross-site POST callbacks.
 */
final class SessionCookieConfigTest extends TestCase
{
    private const VARS = ['SESSION_SECURE_COOKIE', 'SESSION_COOKIE_SAME_SITE'];

    private array $saved = [];

    protected function setUp(): void
    {
        parent::setUp();
        // config/session.php calls storage_path(); an un-booted Application is enough.
        new Application(dirname(__DIR__, 2));
        foreach (self::VARS as $name) {
            $this->saved[$name] = [getenv($name), $_ENV[$name] ?? null, $_SERVER[$name] ?? null];
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $name => [$env, $envConst, $server]) {
            if ($env !== false) putenv("$name=$env");
            if (!is_null($envConst)) $_ENV[$name] = $envConst;
            if (!is_null($server)) $_SERVER[$name] = $server;
        }
        Container::setInstance(null);
        parent::tearDown();
    }

    public function testSessionCookieIsSecureAndSameSiteNoneByDefault(): void
    {
        $config = require __DIR__ . '/../../config/session.php';

        $this->assertTrue($config['secure'], 'session cookie must default to Secure');
        $this->assertSame('none', $config['same_site'], 'session cookie must default to SameSite=None');
    }

    public function testPlainHttpEnvironmentsCanOptOut(): void
    {
        putenv('SESSION_SECURE_COOKIE=false');
        putenv('SESSION_COOKIE_SAME_SITE=lax');

        $config = require __DIR__ . '/../../config/session.php';

        $this->assertFalse($config['secure']);
        $this->assertSame('lax', $config['same_site']);
    }
}
