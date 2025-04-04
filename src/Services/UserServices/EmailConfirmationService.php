<?php

namespace App\Services\UserServices;

use App\Entity\User;
use App\Events\Notifications\EmailNotificationEvent;
use App\Services\UserServices\Dto\EmailConfirmationDto;
use App\Services\UserServices\Exceptions\EmailConfirmationServiceExceptions\EmailBeenConfirmationException;
use App\Services\UserServices\Interfaces\EmailConfirmationServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailConfirmationService implements EmailConfirmationServiceInterface
{
    private EntityManagerInterface $entityManager;
    private string $fromEmail;
    private User $user;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $fromEmail,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->fromEmail = $fromEmail;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @throws EmailBeenConfirmationException
     */
    public function __invoke(EmailConfirmationDto $dto): self
    {
        $user = $dto->getUser();
        if ($user->isHaveRole(USER::IS_AUTHENTICATED_FULLY)) {
            throw new EmailBeenConfirmationException;
        }
        $user->addRole(USER::IS_AUTHENTICATED_FULLY);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->user = $user;
        return $this;
    }

    public function sendEmail(): void
    {
        $this->dispatcher->dispatch(
            new EmailNotificationEvent(
                $this->fromEmail,
                $this->user->getEmail(),
                'Подтверждение электронной почты',
                "Ваша электронная почта подтверждена."
            ),
            EmailNotificationEvent::NAME,
        );
    }

}