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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Class UserEmailVerificationRequest
 * @package App\Mail
 */
final class UserEmailVerificationRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $tries = 2;

    /**
     * @var string
     */
    public $verification_link;

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
    public $bio_link;

    /**
     * The subject of the message.
     *
     * @var string
     */
    public $subject;

    /**
     * UserEmailVerificationRequest constructor.
     * @param User $user
     * @param string $verification_link
     */
    public function __construct(User $user, string $verification_link)
    {
        $this->verification_link = $verification_link;
        $this->user_email = $user->getEmail();
        $this->user_fullname = $user->getFullName();
        $this->bio_link = URL::action("UserController@getLogin");
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $this->subject = sprintf("[%s] Email verification required", Config::get('app.app_name'));
        $view = 'emails.auth.email_verification_request';
        if(Config::get("app.tenant_name") == 'FNTECH') {
            $view = 'emails.auth.email_verification_request_fn';
        }
        Log::debug(sprintf("UserEmailVerificationRequest::build to %s", $this->user_email));
        return $this->from(Config::get("mail.from"))
            ->to($this->user_email)
            ->subject($this->subject)
            ->view($view);
    }
}
