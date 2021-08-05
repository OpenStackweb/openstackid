<?php namespace App\Mail;
/**
 * Copyright 2016 OpenStack Foundation
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
 * Class UserEmailVerificationSuccess
 * @package App\Mail
 */
class UserEmailVerificationSuccess extends Mailable
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
    public $reset_password_link;

    /**
     * minutes
     * @var int
     */
    public $reset_password_link_lifetime;

    /**
     * The subject of the message.
     *
     * @var string
     */
    public $subject;

    /**
     * UserEmailVerificationSuccess constructor.
     * @param User $user
     * @param string|null $reset_password_link
     */
    public function __construct
    (
        User $user,
        ?string $reset_password_link
    )
    {
        $this->user_email = $user->getEmail();
        $this->user_fullname = $user->getFullName();
        $this->reset_password_link = $reset_password_link;
        $this->reset_password_link_lifetime = Config::get("auth.password_reset_lifetime")/60;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject = sprintf('[%1$s] %1$s Verified', Config::get('app.app_name'));
        Log::debug(sprintf("UserEmailVerificationSuccess::build to %s", $this->user_email));
        return $this->from(Config::get("mail.from"))
            ->to($this->user_email)
            ->subject($this->subject)
            ->view('emails.auth.email_verification_request_success');
    }
}
