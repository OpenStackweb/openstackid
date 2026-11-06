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
use Illuminate\Support\Facades\URL;

/**
 * Class TwoFactorEnabledByAdminMail
 * Tells a user that an administrator turned two-factor authentication on for their
 * account and that they should generate their recovery codes from the profile page.
 * The mail never carries recovery codes.
 * @package App\Mail
 */
class TwoFactorEnabledByAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tries = 2;

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

    /**
     * The subject of the message.
     *
     * @var string
     */
    public $subject;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user_email = $user->getEmail();
        $this->user_fullname = $user->getFullName();
        $this->profile_url = URL::action('UserController@getProfile');
    }

    /**
     * @return $this
     */
    public function build()
    {
        $this->subject = sprintf('[%s] Two-factor authentication enabled', Config::get('app.app_name'));
        Log::debug(sprintf("TwoFactorEnabledByAdminMail::build to %s", $this->user_email));
        return $this->from(Config::get("mail.from"))
            ->to($this->user_email)
            ->subject($this->subject)
            ->view('emails.auth.two_factor_enabled_by_admin');
    }
}
