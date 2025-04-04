<?php

namespace App\Services\AdminServices;

use App\Services\AdminServices\Dto\UserDto;
use App\Services\AdminServices\Interfaces\UserBlockServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

class UserBlockService implements UserBlockServiceInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(UserDto $dto): void
    {
        $user = $dto->getUser();

        $user->setIsActivated(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}