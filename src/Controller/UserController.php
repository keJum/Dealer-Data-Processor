<?php

namespace App\Controller;

use App\Dto\Exceptions\ValidateDtoErrorException;
use App\Dto\Exceptions\ValidateDtoWarningException;
use App\Services\UserServices\Dto\EmailConfirmationDto;
use App\Services\UserServices\Dto\RegistrationDto;
use App\Services\UserServices\Dto\PasswordResetConfirmDto;
use App\Services\UserServices\Dto\PasswordResetDto;
use App\Services\UserServices\Exceptions\EmailConfirmationServiceExceptions\EmailBeenConfirmationException;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\NotFoundUserException;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenResetIsActualException;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenResetTimeIsOver;
use App\Services\UserServices\Exceptions\RegistrationServiceException;
use App\Services\UserServices\Exceptions\RegistrationServiceExceptions\EmailNotUniqueException;
use App\Services\UserServices\Interfaces\EmailConfirmationServiceInterface;
use App\Services\UserServices\Interfaces\PasswordResetConfirmationInterface;
use App\Services\UserServices\Interfaces\RegistrationServiceInterface;
use App\Services\UserServices\Interfaces\PasswordResetServiceInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="User")
 */
class UserController extends DefaultController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/user/registration", name="user_registration", methods={"POST"}, )
     * @OA\Post(
     *     summary="регистрация нового пользователя и отправка на электронную почту токена для авторизации"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     description="Данные для регистрации",
     *     @OA\JsonContent(
     *          @OA\Property(property="email", type="string", format="email", example="user@email.com"),
     *          @OA\Property(property="password", type="string", format="password", example="password1234")
     *     )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Пользователь был создан",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Ok"),
     *          @OA\Property(property="message", type="string", example="На электронную почту отправлено сообщение для подтверждения")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Ошибка в веденных данных",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     * @OA\Response(
     *     response=500,
     *     description="Ошибка на стороне сервера",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     */
    public function registration(
        Request $request,
        RegistrationDto $dto,
        RegistrationServiceInterface $userService
    ): JsonResponse {
        try {
            $dto->setByEmailAndPassword(
                $request->toArray()['email'],
                $request->toArray()['password']
            );
            $userService($dto)->sendEmail();
            $response = $this->jsonResponseOk(
                'На электронную почту отправлено сообщение для подтверждения',
                Response::HTTP_CREATED
            );
        } catch (EmailNotUniqueException $exception) {
            $this->logger->info($exception);
            $response = $this->jsonResponseErrorClient('Указанный email уже используется');
        } catch (ValidateDtoWarningException $exception) {
            $this->logger->warning($exception);
            $response = $this->jsonResponseErrorClient($exception->getMessage());
        } catch (TransportExceptionInterface $exception) {
            $this->logger->warning($exception);
            $response = $this->jsonResponseErrorServer('Не удалось отправить сообщение');
        } catch (RegistrationServiceException $exception) {
            $this->logger->error($exception);
            $response = $this->jsonResponseErrorServer('При регистрации произошла ошибка');
        }
        return $response;
    }

    /**
     * @Route("/user/email/confirmation", name="user_email_confirmation", methods={"POST"})
     * @OA\Post(
     *     summary="Подтверждение регистрации через элекстронную почту"
     * )
     * @OA\Parameter(
     *     required=true,
     *     name="X-AUTH-TOKEN",
     *     in="header",
     *     description="Токен потверждения из электронной почты",
     *     @OA\Schema(type="string")
     * )
     * @OA\Response(
     *     response=202,
     *     description="Операция выполнена успешна",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Ok"),
     *          @OA\Property(property="message", type="string", example="Электронная почта подтверждена")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Ошибка в веденных данных",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     * @OA\Response(
     *     response=500,
     *     description="Ошибка на стороне сервера",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     */
    public function emailConfirmation(
        EmailConfirmationServiceInterface $userService,
        EmailConfirmationDto $dto
    ): JsonResponse {
        try {
            $dto->setByUser($this->getUser());
            $userService($dto);
            $response = $this->jsonResponseOk(
                'Электронная почта подтверждена',
                Response::HTTP_ACCEPTED
            );
        } catch (EmailBeenConfirmationException $exception) {
            $this->logger->notice($exception);
            $response = $this->jsonResponseErrorClient(
                'Почта уже была ранее активирована'
            );
        } catch (ValidateDtoErrorException $exception) {
            $this->logger->error($exception);
            $response = $this->jsonResponseErrorServer(
                'На сайте произошла ошибка обратитесь к администратору сайта'
            );
        }
        return $response;
    }

    /**
     * @Route("/user/password/reset", name="user_password_reset", methods={"POST"})
     * @OA\Post(
     *     summary="Запрос на смену пароля",
     * )
     * @OA\RequestBody(
     *     required=true,
     *     description="Электронная почта для сброса пароля",
     *     @OA\JsonContent(
     *          @OA\Property(property="email", type="string", format="email", example="user@email.com"),
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Операция выполнена успешна",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Ok"),
     *          @OA\Property(property="message", type="string", example="На электронную почту отправлено токен для сброса пароля'")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Ошибка в веденных данных",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     * @OA\Response(
     *     response=500,
     *     description="Ошибка на стороне сервера",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     */
    public function reset(
        Request $request,
        PasswordResetDto $dto,
        PasswordResetServiceInterface $userService
    ): JsonResponse {
        try {
            $dto->setByEmail($request->toArray()['email']);
            $userService($dto)->sendEmail();
            $response = $this->jsonResponseOk('На электронную почту отправлено токен для сброса пароля');
        } catch (ValidateDtoWarningException $exception) {
            $this->logger->notice($exception);
            $response = $this->jsonResponseErrorClient($exception->getMessage());
        } catch (TokenResetIsActualException $exception) {
            $this->logger->notice($exception);
            $response = $this->jsonResponseErrorClient('На электронную почту уже был выслан токен');
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error($exception);
            $response = $this->jsonResponseErrorServer('Не удалось отправить сообщение');
        } catch (Throwable | Exception $exception) {
            $this->logger->error($exception);
            $response = $this->jsonResponseErrorServer('На сервере произошла ошибка сообщите администратору');
        }
        return $response;
    }

    /**
     * @Route("/user/password/reset/confirmation", name="user_password_reset_confirmation", methods={"POST"})
     * @OA\Post(
     *     summary="Подтверждение сброса пароля через элекстронную почту"
     * )
     * @OA\Parameter(
     *     required=true,
     *     name="X-RESET-TOKEN",
     *     in="header",
     *     description="Токен потверждения из электронной почты",
     *     @OA\Schema(type="string")
     * )
     * @OA\Response(
     *     response=202,
     *     description="Операция выполнена успешна",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Ok"),
     *          @OA\Property(property="message", type="string", example="Пароль был изменен")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Ошибка в веденных данных",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     * @OA\Response(
     *     response=500,
     *     description="Ошибка на стороне сервера",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Error"),
     *          @OA\Property(property="message", type="string", example="Текст ошибки")
     *     )
     * )
     */
    public function resetConfirmation(
        Request $request,
        PasswordResetConfirmDto $dto,
        PasswordResetConfirmationInterface $service
    ): JsonResponse {
        try {
            $dto->setByPasswordAndUserAndToken(
                $request->toArray()['password'],
                $this->getUser(),
                $request->headers->get('X-RESET-TOKEN')
            );
            $service($dto);
            $response = $this->jsonResponseOk(
                'Пароль был изменен',
                Response::HTTP_ACCEPTED
            );
        } catch (ValidateDtoWarningException $exception) {
            $this->logger->info($exception);
            $response = $this->jsonResponseErrorClient($exception->getMessage());
        } catch (TokenResetTimeIsOver $exception) {
            $this->logger->notice($exception);
            $response = $this->jsonResponseErrorClient("Время действия токена закончилось");
        } catch (ValidateDtoErrorException | NotFoundUserException $exception) {
            $this->logger->error($exception);
            $response = $this->jsonResponseErrorServer('На сервере произошла ошибка сообщите администратору');
        }
        return $response;
    }
}
