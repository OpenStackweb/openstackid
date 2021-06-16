<?php namespace App\Mail;
/**
 * Copyright 2021 OpenStack Foundation
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
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class OAuth2PasswordlessOTPMail
 * @package App\Mail
 */
class OAuth2PasswordlessOTPMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tries = 3;

    /**
     * @var string
     */
    public $otp;

    /**
     * @var string
     */
    public $email;

    /**
     * @var int
     */
    public $lifetime;

    /**
     * The subject of the message.
     *
     * @var string
     */
    public $subject;

    /**
     * OAuth2PasswordlessOTPMail constructor.
     * @param string $to
     * @param string $otp
     * @param int $lifetime
     */
    public function __construct
    (
        string $to,
        string $otp,
        int $lifetime
    )
    {
        $this->email = $to;
        $this->otp = $otp;
        $this->lifetime = $lifetime / 60;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject = sprintf("[%s] Your Verification Code", Config::get('app.app_name'));
        Log::debug(sprintf("OAuth2PasswordlessOTPMail::build to %s", $this->email));
        return $this->from(Config::get("mail.from"))
            ->to($this->email)
            ->subject($this->subject)
            ->view('emails.oauth2_passwordless_otp');
    }
}