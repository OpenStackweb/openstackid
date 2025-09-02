<?php namespace App\Mail;
/*
 * Copyright 2025 OpenStack Foundation
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
 * Class MonitoredSecurityGroupNotificationEmail
 * @package App\Mail
 */
final class MonitoredSecurityGroupNotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $tries = 3;

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $action_by;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $user_email;

    /*
     * @var string
     */
    public $user_name;

    /**
     * @var int
     */
    public $group_id;

    /**
     * @var string
     */
    public $group_name;

    /**
     * @var string
     */
    public $group_slug;

    /**
     * @var string
     */
    public $email;

    /**
     * The subject of the message.
     *
     * @var string
     */
    public $subject;

    /**
     * @param string $email
     * @param string $action
     * @param string $action_by
     * @param int $user_id
     * @param string $user_email
     * @param string $user_name
     * @param int $group_id
     * @param string $group_name
     * @param string $group_slug
     */
    public function __construct
    (
        string $email,
        string $action,
        string $action_by,
        int $user_id,
        string $user_email,
        string $user_name,
        int $group_id,
        string $group_name,
        string $group_slug
    )
    {
        $this->email = $email;
        $this->action = $action;
        $this->action_by = $action_by;
        $this->user_id = $user_id;
        $this->user_email = $user_email;
        $this->user_name = $user_name;
        $this->group_id = $group_id;
        $this->group_name = $group_name;
        $this->group_slug = $group_slug;

        Log::debug
        (
            sprintf
            (
                "MonitoredSecurityGroupNotificationEmail::constructor email %s action %s action_by %s user_id %s user_email %s user_name %s group_id %s group_name %s group_slug %s",
                $email,
                $action,
                $action_by,
                $user_id,
                $user_email,
                $user_name,
                $group_id,
                $group_name,
                $group_slug
            )
        );
    }

    public function build()
    {
        $action_by_phrase = $this->action_by ? " by $this->action_by" : "";

        $this->subject = sprintf
        (
            "[%s] Monitored Security Groups - User %s (%s) has been %s%s - Group %s (%s) - Environment: %s"
            ,Config::get('app.app_name')
            ,$this->user_name
            ,$this->user_email
            ,$this->action
            ,$action_by_phrase
            ,$this->group_name
            ,$this->group_id
            ,Config::get('app.env')
        );
        Log::debug(sprintf("MonitoredSecurityGroupNotificationEmail::build to %s", $this->email));
        return $this->from(Config::get("mail.from"))
            ->to($this->email)
            ->subject($this->subject)
            ->view('emails.audit.monitored_security_group_notification');
    }
}