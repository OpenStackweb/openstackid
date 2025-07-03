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
     * @var string|null
     */
    public $reset_password_link;

    /**
     * @var int|null
     */
    public $reset_password_link_lifetime;

    /**
     * @var string|null
     */
    public $client_app_name;

    /**
     * @var string|null
     */
    public $client_terms_of_services_uri;

    /**
     * @var string|null
     */
    public $client_policy_uri;

    /**
     * @var string|null
     */
    public $client_scope_descriptions;

    /**
     * @param string $to
     * @param string $otp
     * @param int $lifetime
     * @param string|null $reset_password_link
     * @param string|null $client_app_name
     * @param string|null $client_terms_of_services_uri
     * @param string|null $client_policy_uri
     * @param array|null $client_scope_descriptions
     */
    public function __construct
    (
        string $to,
        string $otp,
        int $lifetime,
        string $reset_password_link = null,
        string $client_app_name = null,
        string $client_terms_of_services_uri = null,
        string $client_policy_uri = null,
        ?array $client_scope_descriptions = []
    )
    {
        $this->email = trim($to);
        $this->otp = trim($otp);
        $this->lifetime = $lifetime / 60;
        $this->reset_password_link = $reset_password_link;
        $this->reset_password_link_lifetime = Config::get("auth.password_reset_lifetime")/60;
        $this->client_app_name = $client_app_name;
        $this->client_terms_of_services_uri = $client_terms_of_services_uri;
        $this->client_policy_uri = $client_policy_uri;
        $this->client_scope_descriptions = $client_scope_descriptions;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject = sprintf("[%s] %s is your Single-use Code", Config::get('app.app_name'), $this->otp);
        Log::debug(sprintf("OAuth2PasswordlessOTPMail::build to %s", $this->email));
        return $this->from(Config::get("mail.from"))
            ->to($this->email)
            ->subject($this->subject)
            ->view('emails.oauth2_passwordless_otp');
    }
}