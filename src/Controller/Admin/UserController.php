<?php

namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Dto\EmailDto;
use App\Dto\Exceptions\ValidateDtoWarningException;
use App\Producer\SendEmailProducer;
use App\Repository\UserRepository;
use App\Services\AdminServices\Interfaces\UserBlockServiceInterface;
use App\Services\AdminServices\Interfaces\UserDeleteServiceInterface;
use App\Services\AdminServices\Dto\UserDto;
use App\Services\AdminServices\Interfaces\UserListServiceInterface;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Admin")
 */
class UserController extends DefaultController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/api/admin/user/list", name="admin_user_list", methods={"GET"})
     * @OA\Get(
     *     summary="Получить список почтовых адрессов"
     * )
     * @OA\Parameter(
     *     required=true,
     *     name="Authorization",
     *     in="header",
     *     description="Токен авторизации",
     *     @OA\Schema(type="string")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Выведен список всех электронных адрессов",
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="Ok"),
     *          @OA\Property(
     *              property="emails",
     *              type="string",
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
    public function list(UserListServiceInterface $listUsersService): Response
    {
        return $this->jsonResponseOk($listUsersService->list());
    }

    /**
     * @Route("/api/admin/user/block", name="admin_user_block", methods={"POST"})
     * @OA\Post (
     *     summary="Блокировка пользователя"
     * )
     * @OA\Parameter(
     *     required=true,
     *     name="Authorization",
     *     in="header",
     *     description="Токен авторизации",
     *     @OA\Schema(type="string")
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="email",
     *              type="string",
     *              example="Почта пользователя которую нужно заблокировать"
     *          )
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Пользователь был деактивирован"
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
    public function block(
        Request $request,
        UserDto $dto,
        UserBlockServiceInterface $service
    ): Response {
        try {
            $dto->setByEmail($request->toArray()['email']);
            $service($dto);
            $response = $this->jsonResponseOk('Пользователь был деактивирован');
        } catch (ValidateDtoWarningException $exception) {
            $this->logger->notice($exception->getMessage());
            $response = $this->jsonResponse($exception->getMessage(), self::STATUS_ERROR);
        }
        return $response;
    }

    /**
     * @Route("/api/admin/user/delete", name="admin_user_delete", methods={"POST"})
     * @OA\Post(
     *     summary="Удаление пользователя"
     * )
     * @OA\Parameter(
     *     required=true,
     *     name="Authorization",
     *     in="header",
     *     description="Токен авторизации",
     *     @OA\Schema(type="string")
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="email",
     *              type="string",
     *              example="Почта пользователя которую нужно заблокировать"
     *          )
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Пользователь удаленн"
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
    public function delete(
        Request $request,
        UserDto $dto,
        UserDeleteServiceInterface $service
    ): Response {
        try {
            $dto->setByEmail($request->toArray()['email']);
            $service($dto);
            $response = $this->jsonResponse("Пользователь удален");
        } catch (ValidateDtoWarningException $exception) {
            $this->logger->notice($exception->getMessage());
            $response = $this->jsonResponse($exception->getMessage(), self::STATUS_ERROR);
        }
        return $response;
    }

    /**
     * @Route("/api/admin/users/send", methods={"POST"})
     * @OA\Post (
     *     summary="Отправка всем пользователям электоронное сообщение"
     * )
     * @OA\Parameter(
     *     required=true,
     *     name="Authorization",
     *     in="header",
     *     description="Токен авторизации"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="subject",
     *              type="string",
     *              example="Заголовок сообщения"
     *          ),
     *          @OA\Property(
     *              property="text",
     *              type="string",
     *              example="Тело сообщения"
     *          )
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Пользователь был деактивирован"
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

    public function sendEmails(
        Request $request,
        SendEmailProducer $producer,
        UserRepository $userRepository,
        EmailDto $emailDto
    ): Response {
        try {
            $emailDto->setFromEmail($this->getParameter('email.send_from'));
            $emailDto->setRequest($request);
            foreach ($userRepository->findAll() as $user) {
                $emailDto->setToEmail($user->getEmail());
                $producer->publish(
                    $emailDto->getJson()
                );
            }
            $response = $this->jsonResponseOk();
        } catch (ValidateDtoWarningException | JsonException $e) {
            $response = $this->jsonResponseErrorClient($e->getMessage());
        }
        return $response;
    }
}