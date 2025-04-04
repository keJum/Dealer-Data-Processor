<?php

namespace App\Tests\Application\Controllers\UserController;

use App\Entity\Token;
use App\Fixtures\UserFixture;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use App\Tests\Application\Controllers\DefaultControllerTest;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @covers \App\Controller\UserController::resetConfirmation
 */
class PasswordResetConfirmationTest extends DefaultControllerTest
{
    protected ?UserRepository $userRepository;
    protected ?TokenRepository $tokenRepository;
    protected ?EntityManager $entityManager;
    protected ?UserPasswordEncoderInterface $userPasswordEncoder;
    protected ?UserFixture $userFixture;
    private string $newPassword;
    private ?Token $tokenAuth;
    private ?Token $tokenReset;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = static::$container->get(UserRepository::class);
        $this->tokenRepository = static::$container->get(TokenRepository::class);
        $this->entityManager = static::$container->get(EntityManagerInterface::class);
        $this->userPasswordEncoder = static::$container->get(UserPasswordEncoderInterface::class);
        $this->userFixture = static::$container->get(UserFixture::class);

        $this->userFixture->load($this->entityManager);

        $this->newPassword = '1234';

        $user = $this->userRepository->findOneBy(
            ['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_PLUS_TOKEN_RESET]
        );
        $this->tokenAuth = $this->tokenRepository->findOneBy(['user' => $user, 'type' => Token::TYPE_AUTH]);
        $this->tokenReset = $this->tokenRepository->findOneBy(['user' => $user, 'type' => Token::TYPE_RESET]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testResetConfirm(): void
    {
        $response = static::createClient()->request('POST', '/user/password/reset/confirmation', [
            'headers' => [
                'X-AUTH-TOKEN' => $this->tokenAuth->getValue(),
                'X-RESET-TOKEN' => $this->tokenReset->getValue()
            ],
            'json' => [
                'password' => $this->newPassword
            ]
        ]);

        $this->assertResponseJson($response, "Пароль был изменен");
        $user = $this->userRepository->findOneBy(
            ['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_PLUS_TOKEN_RESET]
        );
        self::assertNotFalse(
            $this->userPasswordEncoder->isPasswordValid($user, $this->newPassword),
            'Пароль не был заменен'
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testResetConfirmSendTwo(): void
    {
        static::createClient()->request('POST', '/user/password/reset/confirmation', [
            'headers' => [
                'X-AUTH-TOKEN' => $this->tokenAuth->getValue(),
                'X-RESET-TOKEN' => $this->tokenReset->getValue()
            ],
            'json' => [
                'password' => $this->newPassword
            ]
        ]);

        static::createClient()->request('POST', '/user/password/reset/confirmation', [
            'headers' => [
                'X-AUTH-TOKEN' => $this->tokenAuth->getValue(),
                'X-RESET-TOKEN' => $this->tokenReset->getValue()
            ],
            'json' => [
                'password' => $this->newPassword
            ]
        ]);

        self::assertResponseStatusCodeSame(
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testResetConfirmSendNotValidateToken(): void
    {
        static::createClient()->request('POST', '/user/password/reset/confirmation', [
            'headers' => [
                'X-AUTH-TOKEN' => $this->tokenAuth->getValue(),
                'X-RESET-TOKEN' => $this->tokenAuth->getValue()
            ],
            'json' => [
                'password' => $this->newPassword
            ]
        ]);

        self::assertResponseStatusCodeSame(
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testResetConfirmSendNotAuthToken(): void
    {
        static::createClient()->request('POST', '/user/password/reset/confirmation', [
            'headers' => [
                'X-RESET-TOKEN' => $this->tokenReset->getValue()
            ],
            'json' => [
                'password' => $this->newPassword
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testResetConfirmNotSendResetToken(): void
    {
        static::createClient()->request('POST', '/user/password/reset/confirmation', [
            'headers' => [
                'X-AUTH-TOKEN' => $this->tokenAuth->getValue(),
            ],
            'json' => [
                'password' => $this->newPassword
            ]
        ]);
        self::assertResponseStatusCodeSame(
            Response::HTTP_BAD_REQUEST
        );
    }
}