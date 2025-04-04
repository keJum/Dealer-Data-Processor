<?php

namespace App\Services\UserServices\Interfaces;

use App\Services\UserServices\Dto\RegistrationDto;
use App\Services\UserServices\Exceptions\RegistrationServiceException;
use App\Services\UserServices\Exceptions\RegistrationServiceExceptions\EmailNotUniqueException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

interface RegistrationServiceInterface
{
    /**
     * @throws RegistrationServiceException
     * @throws EmailNotUniqueException
     */
    public function __invoke(RegistrationDto $dto): self;

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmail(): self;
}