<?php namespace App\Http\Utils\Log;
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
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\MailHandler;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Illuminate\Support\Facades\Mail;
use Utils\Services\ICacheService;
/**
 * Class LaravelMailerHandler
 * @package App\Http\Utils\Logs
 */
final class LaravelMailerHandler extends MailHandler
{
    // seconds
    const TIME_BETWEEN_ERRORS = 60 * 30;
    const SENT_ERROR_EMAIL = 'SENT_ERROR_EMAIL';
    /**
     * The email addresses to which the message will be sent
     * @var array
     */
    protected $to;

    /**
     * The subject of the email
     * @var string
     */
    protected $subject;

    /**
     * Optional headers for the message
     * @var array
     */
    protected $headers = [];

    /**
     * Optional parameters for the message
     * @var array
     */
    protected $parameters = [];

    /**
     * The wordwrap length for the message
     * @var int
     */
    protected $maxColumnWidth;

    /**
     * The Content-type for the message
     * @var string
     */
    protected $contentType = 'text/plain';

    /**
     * The encoding for the message
     * @var string
     */
    protected $encoding = 'utf-8';

    protected $from = null;

    /**
     * @var ICacheService
     */
    private $cacheService;

    /**
     * @param ICacheService $cacheService
     * @param string|array $to             The receiver of the mail
     * @param string       $subject        The subject of the mail
     * @param string       $from           The sender of the mail
     * @param int          $level          The minimum logging level at which this handler will be triggered
     * @param bool         $bubble         Whether the messages that are handled can bubble up the stack or not
     * @param int          $maxColumnWidth The maximum column width that the message lines will have
     */
    public function __construct(ICacheService $cacheService, $to, $subject, $from, $level = Logger::ERROR, $bubble = true, $maxColumnWidth = 70)
    {
        parent::__construct($level, $bubble);
        $this->from = $from;
        $this->to = is_array($to) ? $to : array($to);
        $this->subject = $subject;
        $this->addHeader(sprintf('From: %s', $from));
        $this->maxColumnWidth = $maxColumnWidth;
        $this->cacheService = $cacheService;
    }

    /**
     * Add headers to the message
     *
     * @param  string|array $headers Custom added headers
     * @return self
     */
    public function addHeader($headers)
    {
        foreach ((array) $headers as $header) {
            if (strpos($header, "\n") !== false || strpos($header, "\r") !== false) {
                throw new \InvalidArgumentException('Headers can not contain newline characters for security reasons');
            }
            $this->headers[] = $header;
        }

        return $this;
    }

    /**
     * Add parameters to the message
     *
     * @param  string|array $parameters Custom added parameters
     * @return self
     */
    public function addParameter($parameters)
    {
        $this->parameters = array_merge($this->parameters, (array) $parameters);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function send($content, array $records)
    {
        $content = wordwrap($content, $this->maxColumnWidth);

        $subject = $this->subject;
        if ($records) {
            $subjectFormatter = new LineFormatter($this->subject);
            $subject = $subjectFormatter->format($this->getHighestRecord($records));
        }

        // to avoid bloating inboxes/quotas
        if($this->cacheService){
            if($this->cacheService->exists(self::SENT_ERROR_EMAIL)){
                // short circuit
                Log::debug(sprintf("LaravelMailerHandler::send skipping exception %s %s", $subject, $content));
                return;
            }
            $this->cacheService->setSingleValue(self::SENT_ERROR_EMAIL, self::SENT_ERROR_EMAIL, LaravelMailerHandler::TIME_BETWEEN_ERRORS);
        }

        foreach ($this->to as $to) {
            Mail::raw($content, function(Message $message) use($to, $subject, $content){
                $message
                    ->to($to)
                    ->subject($subject)
                    ->setBody($content, 'text/html')
                    ->setFrom($this->from);
            });
        }
    }

    /**
     * @return string $contentType
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return string $encoding
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @param  string $contentType The content type of the email - Defaults to text/plain. Use text/html for HTML
     *                             messages.
     * @return self
     */
    public function setContentType($contentType)
    {
        if (strpos($contentType, "\n") !== false || strpos($contentType, "\r") !== false) {
            throw new \InvalidArgumentException('The content type can not contain newline characters to prevent email header injection');
        }

        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @param  string $encoding
     * @return self
     */
    public function setEncoding($encoding)
    {
        if (strpos($encoding, "\n") !== false || strpos($encoding, "\r") !== false) {
            throw new \InvalidArgumentException('The encoding can not contain newline characters to prevent email header injection');
        }

        $this->encoding = $encoding;

        return $this;
    }
}
