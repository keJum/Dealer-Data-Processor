<?php

namespace App\Services\AdminServices;

use App\Repository\TokenRepository;
use App\Services\AdminServices\Dto\UserDto;
use App\Services\AdminServices\Interfaces\UserDeleteServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

class UserDeleteService implements UserDeleteServiceInterface
{
    private EntityManagerInterface $entityManager;
    private TokenRepository $tokenRepository;

    public function __construct(EntityManagerInterface $entityManager, TokenRepository $tokenRepository)
    {
        $this->entityManager = $entityManager;
        $this->tokenRepository = $tokenRepository;
    }

    public function __invoke(UserDto $dto): void
    {
        $user = $dto->getUser();

        $tokens = $this->tokenRepository->findBy(['user' => $user]);
        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}