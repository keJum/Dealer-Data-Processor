<?php

namespace App\Services\AdminServices\Interfaces;

use App\Services\AdminServices\Dto\UserDto;

interface UserDeleteServiceInterface
{
    public function __invoke(UserDto $dto): void;
}