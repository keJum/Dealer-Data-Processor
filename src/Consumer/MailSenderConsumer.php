<?php

namespace App\Consumer;

use App\Dto\EmailDto;
use JsonException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailSenderConsumer implements ConsumerInterface
{

    private MailerInterface $mailer;
    private EmailDto $emailDto;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, EmailDto $emailDto, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->emailDto = $emailDto;
        $this->logger = $logger;
    }

    public function execute(AMQPMessage $msg)
    {
        try{
            $dto = $this->emailDto->setJson($msg->getBody());
            $this->mailer->send(
                (new Email())
                    ->from($dto->getFromEmail())
                    ->to($dto->getToEmail())
                    ->subject($dto->getSubject())
                    ->text($dto->getText())
            );
        } catch (JsonException | TransportExceptionInterface $e) {
            $this->logger->error($e);
        }
    }
}