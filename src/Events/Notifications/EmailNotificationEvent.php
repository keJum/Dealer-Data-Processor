<?php

namespace App\Events\Notifications;

use Symfony\Contracts\EventDispatcher\Event;

class EmailNotificationEvent extends Event
{
    public const NAME = 'notification.email';

    private string $text;
    private string $from;
    private string $to;
    private string $subject;

    public function __construct(string $from, string $to, string $subject, string $text)
    {
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->text = $text;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getText(): string
    {
        return $this->text;
    }
}