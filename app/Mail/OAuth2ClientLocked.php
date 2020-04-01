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
use Models\OAuth2\Client;

/**
 * Class OAuth2ClientLocked
 * @package App\Mail
 */
class OAuth2ClientLocked extends Mailable
{
    use Queueable, SerializesModels;

    public $tries = 1;

    /**
     * @var string
     */
    public $client_id;

    /**
     * @var string
     */
    public $client_name;

    /**
     * @var string
     */
    public $user_email;

    /**
     * @var string
     */
    public $user_fullname;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client_id = $client->getClientId();
        $this->client_name = $client->getApplicationName();
        $this->user_email = $client->getOwner()->getEmail();
        $this->user_fullname = $client->getOwner()->getFullName();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = Config::get("mail.verification_email_subject");
        if(empty($subject))
            $subject = sprintf("[%s] Verify Email Address", Config::get('app.app_name'));

        return $this->from(Config::get("mail.from"))
            ->to($this->user_email)
            ->subject($subject)
            ->view('emails.oauth2_client_locked');
    }
}
