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

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ViewErrorBag;

class PasswordPolicyRenderingTest extends TestCase
{
    /**
     * The regex the browser receives must classify passwords exactly as the
     * server-side one does. Probes are all 10-30 chars so length is never the
     * discriminator — only the special-character class is under test.
     */
    public function test_rendered_reset_pattern_agrees_with_server_pattern(): void
    {
        $html = view('auth.passwords.reset', [
            'token'  => 'irrelevant-for-rendering',
            'email'  => 'probe@example.com',
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertSame(1, preg_match('/shape_pattern:\s*(.+?),\R/', $html, $m));

        // Resolve the \uXXXX escapes a JS parser would resolve in the literal.
        // Trim both single quotes ({{ }} format) and double quotes (Js::from format).
        $client = preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/',
            fn ($u) => mb_chr(hexdec($u[1])),
            trim($m[1], "\"'")
        );
        $server = Config::get('auth.password_shape_pattern');

        foreach (['Passwordma1', 'Passw0rdabc', ';Passw0rdaa', 'Valid1Pass!'] as $probe) {
            $this->assertSame(
                (bool) preg_match("/{$server}/", $probe),
                (bool) preg_match("/{$client}/", $probe),
                "rendered and server patterns disagree on '{$probe}'"
            );
        }
    }
}
