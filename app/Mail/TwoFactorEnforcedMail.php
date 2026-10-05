<?php namespace App\Mail;
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

use Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class TwoFactorEnforcedMail
 * Tells a group-enforced user that MFA is now required on their account.
 * It never carries recovery codes: they are only shown once, in the profile.
 * @package App\Mail
 */
final class TwoFactorEnforcedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tries = 1;

    /**
     * @var string
     */
    public $user_email;

    /**
     * @var string
     */
    public $user_fullname;

    /**
     * @var string
     */
    public $profile_url;

    public function __construct(User $user)
    {
        $this->user_email = $user->getEmail();
        $this->user_fullname = $user->getFullName();
        $this->profile_url = rtrim(Config::get('app.url'), '/') . '/accounts/user/profile';
    }

    /**
     * @return $this
     */
    public function build()
    {
        $subject = sprintf("[%s] Two-factor authentication is now required on your account", Config::get('app.app_name'));
        Log::debug(sprintf("TwoFactorEnforcedMail::build to %s", $this->user_email));
        return $this->from(Config::get("mail.from"))
            ->to($this->user_email)
            ->subject($subject)
            ->view('emails.auth.two_factor_enforced');
    }
}
