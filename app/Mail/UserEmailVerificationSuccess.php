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
     * UserEmailVerificationRequest constructor.
     * @param User $user
     */
    public function __construct(User $user)
    {
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
        $subject = Config::get("mail.verification_sucessfull_email_subject");
        if(empty($subject))
            $subject = sprintf("[%s] You have verified your email address", Config::get('app.app_name'));

        return $this->from(Config::get("mail.from"))
            ->to($this->user_email)
            ->subject($subject)
            ->view('emails.auth.email_verification_request_success');
    }
}
