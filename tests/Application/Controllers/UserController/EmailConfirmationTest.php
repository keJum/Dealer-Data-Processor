<?php

namespace App\Tests\Application\Controllers\UserController;

use App\Entity\Token;
use App\Fixtures\UserFixture;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use App\Tests\Application\Controllers\DefaultControllerTest;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @covers \App\Controller\UserController::emailConfirmation
 */
class EmailConfirmationTest extends DefaultControllerTest
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
    public function testConfirmation(): void
    {
        $token = $this->tokenRepository->findOneBy([
            'user' => $this->userRepository->findOneBy(
                ['email' => UserFixture::EMAIL_USER_NOT_CONFIRMATION_AND_ACTIVATED]
            ),
            'type' => Token::TYPE_AUTH
        ]);

        $response = static::createClient()->request('POST', '/user/email/confirmation', [
            'headers' => [
                'X-AUTH-TOKEN' => $token->getValue()
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $this->assertResponseJson($response, "Электронная почта подтверждена");
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testConfirmationEmailIsConfirmed(): void
    {
        $token = $this->tokenRepository->findOneBy([
            'user' => $this->userRepository->findOneBy(
                ['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED]
            ),
            'type' => Token::TYPE_AUTH
        ]);

        static::createClient()->request('POST', '/user/email/confirmation', [
            'headers' => [
                'X-AUTH-TOKEN' => $token->getValue()
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
     * @throws Exception
     */
    public function testConfirmationNotToken(): void
    {
        static::createClient()->request('POST', '/user/email/confirmation', [
            'headers' => [
                'X-AUTH-TOKEN' => 'not token'
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        static::createClient()->request('POST', '/user/email/confirmation');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}