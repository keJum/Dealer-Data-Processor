<?php

namespace App\Services\UserServices\Interfaces;

use App\Services\UserServices\Dto\PasswordResetConfirmDto;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\NotFoundUserException;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenResetTimeIsOver;

interface PasswordResetConfirmationInterface
{
    /**
     * @throws NotFoundUserException
     * @throws TokenResetTimeIsOver
     */
    public function __invoke(PasswordResetConfirmDto $dto): void;
}