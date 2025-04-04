<?php

namespace App\Tests\Application\Controllers\Admin\UserController;

use App\Fixtures\UserFixture;
use App\Tests\Application\Controllers\DefaultControllerTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @covers \App\Controller\Admin\UserController::list
 */
class ListTest extends DefaultControllerTest
{
    public function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::$container->get(EntityManagerInterface::class);
        static::$container->get(UserFixture::class)->load($entityManager);
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testList(): void
    {
        $authorizationToken = $this->getAuthorizationToken(UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_ADMIN);
        $response = static::createClient()->request('GET', '/api/admin/user/list', [
            'headers' => ['Authorization' => $authorizationToken]
        ]);
        $this->assertResponseStatus($response);

        $responseArray = $response->toArray();
        $this->assertArrayHasKey('message', $responseArray, 'Не найдено поле с сообщением');
        $responseMessage = $response->toArray()['message'];
        $this->assertArrayHasKey('emails', $responseMessage);
        $emails = $responseMessage['emails'];
        $this->assertContains(
            UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED_ADMIN,
            $emails,
            'Не найден пользователь'
        );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function testListForbidden(): void
    {
        $authorizationToken = $this->getAuthorizationToken();
        static::createClient()->request('GET', '/api/admin/user/list', [
            'headers' => ['Authorization' => $authorizationToken]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testListUnauthorized(): void
    {
        static::createClient()->request('GET', '/api/admin/user/list', [
            'headers' => ['Authorization' => '123']
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}