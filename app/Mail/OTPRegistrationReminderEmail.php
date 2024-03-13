<?php namespace App\Mail;
/**
 * Copyright 2024 OpenStack Foundation
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

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class OTPRegistrationReminderEmail
 * @package App\Mail
 */
final class OTPRegistrationReminderEmail extends WelcomeNewUserEmail
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject = sprintf('[%1$s] Remember to set your password', Config::get('app.app_name'));
        $view = 'emails.oauth2_passwordless_otp_reg_reminder';

        if (Config::get("app.tenant_name") == 'FNTECH') {
            $view = 'emails.oauth2_passwordless_otp_reg_reminder_fn';
        }

        Log::debug(sprintf("OTPRegistrationReminderEmail::build to %s", $this->user_email));
        return $this->from(Config::get("mail.from"))
            ->to($this->user_email)
            ->subject($this->subject)
            ->view($view);
    }
}
