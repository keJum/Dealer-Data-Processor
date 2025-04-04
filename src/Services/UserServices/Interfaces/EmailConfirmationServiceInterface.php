<?php

namespace App\Services\UserServices\Interfaces;

use App\Services\UserServices\Dto\EmailConfirmationDto;
use App\Services\UserServices\Exceptions\EmailConfirmationServiceExceptions\EmailBeenConfirmationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

interface EmailConfirmationServiceInterface
{
    /**
     * @throws EmailBeenConfirmationException
     */
    public function __invoke(EmailConfirmationDto $dto): self;

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmail(): void;
}