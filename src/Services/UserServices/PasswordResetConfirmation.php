<?php

namespace App\Services\UserServices;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Services\UserServices\Dto\PasswordResetConfirmDto;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\NotFoundUserException;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenResetTimeIsOver;
use App\Services\UserServices\Interfaces\PasswordResetConfirmationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class PasswordResetConfirmation implements PasswordResetConfirmationInterface
{
    private MailerInterface $mailer;
    private UserPasswordEncoderInterface $passwordEncoder;
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private string $fromEmail;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function __invoke(
        PasswordResetConfirmDto $dto
    ): void {
        $password = $dto->getPassword();
        $token = $dto->getToken();
        $user = $dto->getUser();

        $userByToken = $token->getUser();
        if ($userByToken === null) {
            throw new NotFoundUserException;
        }

        if (!$token->isActualLifetime()) {
            $this->entityManager->remove($token);
            $this->entityManager->flush();
            throw new TokenResetTimeIsOver;
        }

        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                $password
            )
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->entityManager->remove($token);
        $this->entityManager->flush();
    }

}