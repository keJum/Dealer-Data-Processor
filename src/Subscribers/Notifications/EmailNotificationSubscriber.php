<?php

namespace App\Subscribers\Notifications;

use App\Events\Notifications\EmailNotificationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailNotificationSubscriber implements EventSubscriberInterface
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public static function getSubscribedEvents(): array
    {
        return [EmailNotificationEvent::NAME => ['sendEmail']];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmail(EmailNotificationEvent $event): void
    {
        $this->mailer->send(
            (new Email())
                ->from($event->getFrom())
                ->to($event->getTo())
                ->subject($event->getSubject())
                ->text($event->getText())
        );
    }
}