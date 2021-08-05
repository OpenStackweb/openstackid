<?php namespace App\Mail;
/**
 * Copyright 2019 OpenStack Foundation
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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class UserPasswordResetRequestMail
 * @package App\Mail
 */
final class UserPasswordResetRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tries = 2;

    /**
     * @var string
     */
    public $reset_link;

    /**
     * @var string
     */
    public $user_email;

    /**
     * @var string
     */
    public $user_fullname;

    /**
     * The subject of the message.
     *
     * @var string
     */
    public $subject;

    /**
     * UserEmailVerificationRequest constructor.
     * @param User $user
     * @param string $reset_link
     */
    public function __construct(User $user, string $reset_link)
    {
        $this->reset_link = $reset_link;
        $this->user_email = $user->getEmail();
        $this->user_fullname = $user->getFullName();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
      $this->subject = sprintf("[%s] Reset Password Notification", Config::get('app.app_name'));
        Log::debug(sprintf("UserPasswordResetRequestMail::build to %s", $this->user_email));
        return $this->from(Config::get("mail.from"))
            ->to($this->user_email)
            ->subject($this->subject)
            ->view('emails.auth.reset_password_request');
    }
}