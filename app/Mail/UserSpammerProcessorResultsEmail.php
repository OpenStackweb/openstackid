<?php namespace App\Mail;
/**
 * Copyright 2020 OpenStack Foundation
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
/**
 * Class UserSpammerProcessorResultsEmail
 * @package App\Mail
 */
class UserSpammerProcessorResultsEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var array
     */
    public $users;

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $subject = sprintf("[%s] User Spammer Process Result", Config::get('app.app_name'));

        return $this->from(Config::get("mail.from"))
            ->to(Config::get("mail.from"))
            ->subject($subject)
            ->view('emails.user_spammer_process_result');
    }

}