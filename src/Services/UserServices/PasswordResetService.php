<?php

namespace App\Services\UserServices;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Entity\Token;
use App\Entity\User;
use App\Events\Notifications\EmailNotificationEvent;
use App\Services\UserServices\Dto\PasswordResetDto;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenAuthNotFoundException;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenResetIsActualException;
use App\Services\UserServices\Interfaces\PasswordResetServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PasswordResetService implements PasswordResetServiceInterface
{
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private string $fromEmail;
    private User $user;
    private Token $tokenAuth;
    private Token $tokenReset;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        EventDispatcherInterface $dispatcher,
        string $fromEmail
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->dispatcher = $dispatcher;
        $this->fromEmail = $fromEmail;
    }

    public function __invoke(PasswordResetDto $dto): self
    {
        $user = $dto->getUser();

        if ($user->isResetTokenActual()) {
            throw new TokenResetIsActualException();
        }

        $tokenReset = new Token();
        $tokenReset->setTokenReset();
        $this->entityManager->persist($tokenReset);

        $user->addToken($tokenReset);
        $this->entityManager->persist($user);

        $this->entityManager->flush();

        try {
            $tokenAuth = $user->getAuthToken();
        } catch (EntityNotFoundException $exception) {
            throw new TokenAuthNotFoundException;
        }

        $this->user = $user;
        $this->tokenAuth = $tokenAuth;
        $this->tokenReset = $tokenReset;

        return $this;
    }

    public function sendEmail(): self
    {
        $tokenResetLifetime = $this->tokenReset->getLifetime() !== null ? $this->tokenReset->getLifetime()->format(DATE_RSS) : '';
        $this->dispatcher->dispatch(
            new EmailNotificationEvent(
                $this->fromEmail,
                $this->user->getEmail(),
                'Сброс пароля',
                'Токен сброса пароля работает' .
                'до ' . $tokenResetLifetime . PHP_EOL .
                'Для подтверждения сброса пароля отправьте новый пароль ' .
                'в поле password в запросе: ' .
                $this->urlGenerator->generate('user_password_reset_confirmation') .
                ' c заголовком X-AUTH-TOKEN со значением ' . $this->tokenAuth->getValue() . ' ' .
                'и с заголовков X-RESET-TOKEN c значением ' . $this->tokenReset->getValue() . '.'
            ),
            EmailNotificationEvent::NAME
        );

        return $this;
    }
}