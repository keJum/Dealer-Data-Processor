<?php

namespace App\Tests\Application\Controllers\Admin\UserController;

use App\Fixtures\UserFixture;
use App\Repository\UserRepository;
use App\Tests\Application\Controllers\DefaultControllerTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @covers \App\Controller\Admin\UserController::block
 */
class BlockTest extends DefaultControllerTest
{
    private ?UserRepository $userRepository;

    public function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::$container->get(EntityManagerInterface::class);
        static::$container->get(UserFixture::class)->load($entityManager);
        $this->userRepository = static::$container->get(UserRepository::class);
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testBlock(): void
    {
        $authorizationToken = $this->getAuthorizationToken(
            UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_ADMIN
        );
        $response = static::createClient()->request('POST', '/api/admin/user/block',[
            'headers' => ['Authorization' => $authorizationToken],
            'json' => [
                'email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED
            ]
        ]);
        $this->assertResponseJson($response, 'Пользователь был деактивирован');

        $user= $this->userRepository->findOneBy(['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED]);
        $this->assertFalse($user->getIsActivated(), 'Пользователь не был деактивирован');
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testBlockNotFoundEmail(): void
    {
        $authorizationToken = $this->getAuthorizationToken(
            UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_ADMIN
        );
        $response = static::createClient()->request('POST', '/api/admin/user/block',[
            'headers' => ['Authorization' => $authorizationToken],
            'json' => [
                'email' => 'notFoundEmail@gmail.com'
            ]
        ]);
        $this->assertResponseJson($response, 'Не найден пользователь с такой электронной почтой', 'error');

        $user= $this->userRepository->findOneBy(['email' => 'notFoundEmail@gmail.com']);
        $this->assertNull($user, 'Был создан новый пользователь');
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testBlockForbidden(): void
    {
        $authorizationToken = $this->getAuthorizationToken();
        static::createClient()->request('POST', '/api/admin/user/block',[
            'headers' => ['Authorization' => $authorizationToken],
            'json' => [
                'email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $user= $this->userRepository->findOneBy(['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED]);
        $this->assertTrue($user->getIsActivated(), 'Пользователь был деактивирован');
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testBlockUnauthorized(): void
    {
        static::createClient()->request('POST', '/api/admin/user/block',[
            'headers' => ['Authorization' => 'not validate token'],
            'json' => [
                'email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $user= $this->userRepository->findOneBy(['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED]);
        $this->assertTrue($user->getIsActivated(), 'Пользователь был деактивирован');
    }

}