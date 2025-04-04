<?php

namespace App\Fixtures;

use App\Entity\Token;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixture extends Fixture
{
    public const EMAIL_USER_NOT_CONFIRMATION_AND_ACTIVATED = 'fixtureNotConfiramtionAndActivate@gmail.com';
    public const EMAIL_USER_NOT_CONFIRMATION_AND_NOT_ACTIVATED = 'fixtureNotConfiramtionAndNotActivate@gmail.com';
    public const EMAIL_USER_CONFIRMATION_AND_ACTIVATED = 'fixtureConfiramtionAndActivate@gmail.com';
    public const EMAIL_USER_CONFIRMATION_AND_NOT_ACTIVATED = 'fixtureConfiramtionAndNotActivate@gmail.com';
    public const EMAIL_USER_CONFIRMATION_AND_ACTIVATED_PLUS_TOKEN_RESET = 'fixtureConfiramtionAndActivatePlusTokenReset@gmail.com';
    public const EMAIL_USER_CONFIRMATION_AND_ACTIVATED_ADMIN = 'fixtureConfiramtionAndActivateAdmin@gmail.com';

    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager): void
    {
        $this->createUser(
            $manager,
            self::EMAIL_USER_CONFIRMATION_AND_ACTIVATED,
            [Token::TYPE_AUTH]
        );
        $this->createUser(
            $manager,
            self::EMAIL_USER_CONFIRMATION_AND_NOT_ACTIVATED,
            [Token::TYPE_AUTH],
            true,
            false
        );
        $this->createUser(
            $manager,
            self::EMAIL_USER_NOT_CONFIRMATION_AND_ACTIVATED,
            [Token::TYPE_AUTH],
            false
        );
        $this->createUser(
            $manager,
            self::EMAIL_USER_NOT_CONFIRMATION_AND_NOT_ACTIVATED,
            [Token::TYPE_AUTH],
            false,
            false
        );
        $this->createUser(
            $manager,
            self::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_PLUS_TOKEN_RESET,
            [Token::TYPE_AUTH, Token::TYPE_RESET]
        );
        $this->createUser(
            $manager,
            self::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_ADMIN,
            [Token::TYPE_AUTH],
            true,
            true,
            true
        );
        $manager->flush();
    }

    private function createToken(ObjectManager $manager, string $typeToken): Token
    {
        $token = new Token();
        switch ($typeToken){
            case Token::TYPE_AUTH:
                $token->setTokenAuth();
                break;
            case Token::TYPE_RESET:
                $token->setTokenReset();
                break;
        }
        $manager->persist($token);
        $manager->flush();
        return $token;
    }

    private function createUser(
        ObjectManager $manager,
        string $email,
        array $typeTokens,
        bool $isConfirmed = true,
        bool $isActivated = true,
        bool $isAdmin = false
    ): void {
        $tokens = [];
        foreach ($typeTokens as $typeToken) {
            $tokens[] = $this->createToken($manager, $typeToken);
        }
        $user = new User;
        $user->setEmail($email);
        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                '123'
            )
        );
        $user->addRole(User::ROLE_USER);
        if ($isConfirmed) {
            $user->addRole(USER::IS_AUTHENTICATED_FULLY);
        }
        if ($isAdmin) {
            $user->addRole(USER::ROLE_ADMIN);
        }
        $user->setIsActivated($isActivated);
        foreach ($tokens as $token) {
            $user->addToken($token);
        }
        $manager->persist($user);
    }
}