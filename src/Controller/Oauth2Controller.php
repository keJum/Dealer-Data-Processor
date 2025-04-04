<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JsonException;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Trikoder\Bundle\OAuth2Bundle\League\Repository\AccessTokenRepository;
use Trikoder\Bundle\OAuth2Bundle\League\Repository\RefreshTokenRepository;
use Trikoder\Bundle\OAuth2Bundle\Model\AccessToken;
use Trikoder\Bundle\OAuth2Bundle\Model\RefreshToken;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="OAth2")
 */
class Oauth2Controller extends AbstractController
{
    private AuthorizationServer $server;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        AuthorizationServer $server,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->server = $server;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @Route("/token/auth", name="token_auth", methods={"POST"})
     * @throws Exception
     * @OA\Post(
     *     summary="Получение токенов для работы с сервером"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     description="Данные для регистрации",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="client_id",
     *              type="string",
     *              example="Переданный для входя id пользователя"
     *          ),
     *          @OA\Property(
     *              property="client_secret",
     *              type="string",
     *              example="Переданный для входа секретный токен"
     *          ),
     *          @OA\Property(property="username", type="string", format="email", example="user@email.com"),
     *          @OA\Property(property="password", type="string", format="password", example="password1234")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Выведенны данные для работы с сервером",
     *     @OA\JsonContent(
     *          @OA\Property(property="token_type", type="string", example="Bearer"),
     *          @OA\Property(property="expires_in", type="int", example="3600"),
     *          @OA\Property(
     *              property="access_token",
     *              type="string",
     *              example="Токен для доступа, который указывается в поле Authrization в header запроса"
     *          ),
     *          @OA\Property(
     *              property="refresh_token",
     *              type="string",
     *              example="Токен для сброса токена и получения нового или для повторной авторизации через указанное время в expires_in"
     *          )
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Не удалось авторизоваться",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     */
    public function auth(
        Request $request,
        ServerRequestInterface $serverRequest,
        UserRepository $userRepository,
        UserPasswordEncoderInterface $userPasswordEncoder
    ): ResponseInterface {
        $serverRequest = $serverRequest->withParsedBody([
            'grant_type' => 'password',
            'client_id' => $request->toArray()['client_id'],
            'client_secret' => $request->toArray()['client_secret'],
            'username' => $request->toArray()['username'],
            'password' => $request->toArray()['password']
        ]);
        try {
            $bodyRequest = $serverRequest->getParsedBody();

            if (!array_key_exists("username", $bodyRequest) || !array_key_exists("password", $bodyRequest)) {
                throw new RuntimeException("В запросе не были переданы пароль или логин");
            }
            $user = $userRepository->findOneBy(['email' => $bodyRequest['username']]);
            if (!$user || !$userPasswordEncoder->isPasswordValid($user, $bodyRequest['password'])) {
                throw new RuntimeException("Неверные логин или пароль");
            }

            $response = $this->server->respondToAccessTokenRequest(
                $serverRequest,
                $this->responseFactory->createResponse()
            );
        } catch (RuntimeException | OAuthServerException $exception) {
            $response = $this->jsonResponse(
                $exception->getMessage(),
                DefaultController::STATUS_ERROR,
                Response::HTTP_UNAUTHORIZED
            );
        }
        return $response;
    }

    /**
     * @Route("/token/update", name="token_update", methods={"POST"})
     * @throws Exception
     * @OA\Post(
     *     summary="Обновление токена пользователя"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     description="Данные для регистрации",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="client_id",
     *              type="string",
     *              example="Переданный для входя id пользователя"
     *          ),
     *          @OA\Property(
     *              property="client_secret",
     *              type="string",
     *              example="Переданный для входа секретный токен"
     *          ),
     *          @OA\Property(property="refresh_token", type="string", example="Токен для обновления"),
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Выведенны новые данные для работы с сервером",
     *     @OA\JsonContent(
     *          @OA\Property(property="token_type", type="string", example="Bearer"),
     *          @OA\Property(property="expires_in", type="int", example="3600"),
     *          @OA\Property(
     *              property="access_token",
     *              type="string",
     *              example="Токен для доступа, который указывается в поле Authrization в header запроса"
     *          ),
     *          @OA\Property(
     *              property="refresh_token",
     *              type="string",
     *              example="Токен для сброса токена и получения нового или для повторной авторизации через указанное время в expires_in"
     *          )
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Не удалось авторизоваться",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     */
    public function update(
        ServerRequestInterface $serverRequest
    ): ResponseInterface {
        $serverRequest = $serverRequest
            ->withParsedBody(['grant_type' => 'refresh_token'] + $serverRequest->getParsedBody());
        try {
            $response = $this->server->respondToAccessTokenRequest(
                $serverRequest,
                $this->responseFactory->createResponse()
            );
        } catch (OAuthServerException $exception) {
            $response = $this->jsonResponse(
                $exception->getMessage(),
                DefaultController::STATUS_ERROR,
                Response::HTTP_UNAUTHORIZED
            );
        }
        return $response;
    }

    /**
     * @Route("/api/logout", name="token_logout", methods={"POST"})
     * @throws Exception
     * @OA\Post(
     *     summary="Сброс стокенов авторизации"
     * )
     * @OA\Parameter(
     *     required=true,
     *     name="Authorization",
     *     in="header",
     *     description="Токен авторизации",
     * )
     * @OA\Response(
     *     response=200,
     *     description="Операция выполнена успешна",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Ok"),
     *          @OA\Property(property="message", type="string", example="Токены били сброшены")
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Не удалось авторизоваться",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     */
    public function logout(
        AccessTokenRepository $accessTokenRepository,
        RefreshTokenRepository $refreshTokenRepository,
        EntityManagerInterface $entityManager
    ): ResponseInterface {
        /** @var User $user */
        $user = $this->getUser();
        $accessTokens = $entityManager
            ->getRepository(AccessToken::class)
            ->findBy(['userIdentifier' => $user->getEmail()]);
        $refreshTokenRepositoryModel = $entityManager
            ->getRepository(RefreshToken::class);
        foreach ($accessTokens as $accessToken) {
            $accessTokenRepository->revokeAccessToken($accessToken->getIdentifier());
            $refreshTokens = $refreshTokenRepositoryModel->findBy(['accessToken' => $accessToken->getIdentifier()]);
            foreach ($refreshTokens as $refreshToken) {
                $refreshTokenRepository->revokeRefreshToken($refreshToken->getIdentifier());
            }
        }

        return $this->jsonResponse("Токены били сброшены");
    }

    /**
     * @throws JsonException
     */
    private function jsonResponse(
        string $message,
        string $status = DefaultController::STATUS_OK,
        string $httpStatus = Response::HTTP_OK
    ): ResponseInterface {
        $data = [
            'status' => $status,
            'message' => $message
        ];
        $serverResponse = $this->responseFactory->createResponse();
        $response = $serverResponse
            ->withStatus($httpStatus)
            ->withHeader('pragma', 'no-cache')
            ->withHeader('cache-control', 'no-store')
            ->withHeader('content-type', 'application/json; charset=UTF-8');
        $responseParams = json_encode($data, JSON_THROW_ON_ERROR);
        $response->getBody()->write($responseParams);
        return $response;
    }
}