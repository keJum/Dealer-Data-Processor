<?php

namespace App\Services\UserServices;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Entity\Token;
use App\Entity\User;
use App\Events\Notifications\EmailNotificationEvent;
use App\Repository\UserRepository;
use App\Services\UserServices\Dto\RegistrationDto;
use App\Services\UserServices\Exceptions\RegistrationServiceException;
use App\Services\UserServices\Exceptions\RegistrationServiceExceptions\EmailNotUniqueException;
use App\Services\UserServices\Interfaces\RegistrationServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationService implements RegistrationServiceInterface
{
    private UserRepository $userRepository;
    private MailerInterface $mailer;
    private UserPasswordEncoderInterface $passwordEncoder;
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private string $fromEmail;
    private User $user;
    private Token $token;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $urlGenerator,
        string $fromEmail
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->dispatcher = $dispatcher;
        $this->urlGenerator = $urlGenerator;
        $this->fromEmail = $fromEmail;
    }

    public function __invoke(RegistrationDto $dto): self
    {
        $email = $dto->getEmail();
        $password = $dto->getPassword();

        $isHaveEmail = (bool)$this->userRepository->findOneBy(['email' => $email]);
        if ($isHaveEmail) {
            throw new EmailNotUniqueException;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                $password
            )
        );
        $user->addRole(User::ROLE_USER);

        $token = new Token();
        try {
            $token->setTokenAuth($user->getEmail());
        } catch (Exception $e) {
            throw new RegistrationServiceException($e);
        }
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $user->addToken($token);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token->setUser($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->user = $user;
        $this->token = $token;
        return $this;
    }

    public function sendEmail(): self
    {
        $this->dispatcher->dispatch(
            new EmailNotificationEvent(
                $this->fromEmail,
                $this->user->getEmail(),
                "Пожалуйста, подтвердите вашу почту",
                'Для подтверждения почты отправьте запрос: ' .
                $this->urlGenerator->generate('user_email_confirmation') .
                'с заголовком X-AUTH-TOKEN со значением ' . $this->token->getValue() . '.'
            ),
            EmailNotificationEvent::NAME
        );
        return $this;
    }
}