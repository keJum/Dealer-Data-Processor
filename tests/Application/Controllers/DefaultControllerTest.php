<?php

namespace App\Tests\Application\Controllers;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Fixtures\UserFixture;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Trikoder\Bundle\OAuth2Bundle\Manager\ClientManagerInterface;
use Trikoder\Bundle\OAuth2Bundle\Model\Client;

/**
 * @coversDefaultClass \App\Controller\UserController
 */
abstract class DefaultControllerTest extends ApiTestCase
{
    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function assertResponseJson(ResponseInterface $response, string $message, string $type = 'ok'): void
    {
        $messageResponse = $response->toArray()['message'];
        $this->assertResponseStatus($response, $messageResponse, $type);
        $this->assertEquals($message, $messageResponse);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function assertResponseStatus(
        ResponseInterface $response,
        ?string $message = null,
        string $type = 'ok'
    ): void {
        $typeResponse = $response->toArray()['status'];
        if ($message === null) {
            $message = 'Ответ response не является ' . $type . ', а имеет тип: ' . $typeResponse;
        }
        $this->assertEquals($type, $typeResponse, $message);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws DecodingExceptionInterface
     */
    protected function getAuthorizationToken($email = UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED): string
    {
        self::bootKernel();
        $clientManager = static::$container->get(ClientManagerInterface::class);
        $identifier = hash('md5', random_bytes(16));
        $secret = hash('sha512', random_bytes(32));
        $client = new Client($identifier, $secret);
        $client->setActive(true);
        $clientManager->save($client);

        $response = static::createClient()->request('POST', '/token/auth', [
            'json' => [
                'client_id' => $identifier,
                'client_secret' => $secret,
                'username' => $email,
                'password' => '123'
            ],
//            'headers' => ['Content-Type:' => 'application/x-www-form-urlencoded']
        ]);

        return $response->toArray()['access_token'];
    }
}
