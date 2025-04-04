<?php

namespace App\Services\UserServices\Interfaces;

use App\Services\UserServices\Dto\PasswordResetDto;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenAuthNotFoundException;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenResetIsActualException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

interface PasswordResetServiceInterface
{
    /**
     * @throws TokenAuthNotFoundException
     * @throws TokenResetIsActualException
     * @throws TransportExceptionInterface
     */
    public function __invoke(PasswordResetDto $dto): self;

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmail(): self;
}