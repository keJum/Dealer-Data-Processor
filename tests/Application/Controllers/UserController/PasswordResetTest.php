<?php

namespace App\Tests\Application\Controllers\UserController;

use App\Entity\Token;
use App\Fixtures\UserFixture;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use App\Tests\Application\Controllers\DefaultControllerTest;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @covers \App\Controller\UserController::reset
 */
class PasswordResetTest extends DefaultControllerTest
{
    protected ?UserRepository $userRepository;
    protected ?TokenRepository $tokenRepository;
    protected ?EntityManager $entityManager;
    protected ?UserFixture $userFixture;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = static::$container->get(UserRepository::class);
        $this->tokenRepository = static::$container->get(TokenRepository::class);
        $this->entityManager = static::$container->get(EntityManagerInterface::class);
        $this->userFixture = static::$container->get(UserFixture::class);

        $this->userFixture->load($this->entityManager);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testReset(): void
    {
        $response = static::createClient()->request('POST', '/user/password/reset', [
            'json' => [
                'email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED
            ]
        ]);
        $this->assertResponseJson($response, "На электронную почту отправлено токен для сброса пароля");

        $user = $this->userRepository->findOneBy(['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED]);
        $tokens = $this->tokenRepository->findBy(['user' => $user, 'type' => Token::TYPE_RESET]);
        self::assertNotNull($tokens, "Токен сброса пароля не был создан");
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testResetTokenIsBeenSender(): void
    {
        static::createClient()->request('POST', '/user/password/reset', [
            'json' => [
                'email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_PLUS_TOKEN_RESET
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ORMException
     */
    public function testResetUpdateTokenLifetime(): void
    {
        $user = $this->userRepository->findOneBy(['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_PLUS_TOKEN_RESET]);
        $tokens = $this->tokenRepository->findBy(['user' => $user, 'type' => Token::TYPE_RESET]);
        foreach ($tokens as $token) {
            $token->setLifetime(new DateTime('2000-01-01'));
            $this->entityManager->persist($token);
        }
        $this->entityManager->flush();

        $response = static::createClient()->request('POST', '/user/password/reset', [
            'json' => [
                'email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_PLUS_TOKEN_RESET
            ]
        ]);
        $this->assertResponseJson($response, "На электронную почту отправлено токен для сброса пароля");
    }
}