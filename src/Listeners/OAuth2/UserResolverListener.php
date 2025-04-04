<?php

namespace App\Listeners\OAuth2;

use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Trikoder\Bundle\OAuth2Bundle\Event\UserResolveEvent;

class UserResolverListener
{
    private UserPasswordEncoderInterface $userPasswordEncoder;

    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userRepository = $userRepository;
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    public function onUserResolve(UserResolveEvent $event): void
    {
        $user = $this->userRepository->findOneBy(['email' => $event->getUsername()]);
        if ($user === null || !$user->getIsActivated()) {
            return;
        }
        if (!$this->userPasswordEncoder->isPasswordValid($user, $event->getPassword())) {
            return;
        }

        $event->setUser($user);
    }
}