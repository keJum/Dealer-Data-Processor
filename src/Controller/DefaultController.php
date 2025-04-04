<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class DefaultController extends AbstractController
{
    public const STATUS_OK = 'ok';
    public const STATUS_ERROR = 'error';

    public function jsonResponse(
        $message,
        string $status = self::STATUS_OK,
        int $statusHttp = Response::HTTP_OK
    ): JsonResponse {
        return $this->json(
            [
                'status' => $status,
                'message' => $message
            ],
            $statusHttp
        );
    }

    public function jsonResponseOk(
        $message = '',
        int $httpStatues = Response::HTTP_OK
    ): JsonResponse {
        if (empty($message)) {
            $httpStatues = Response::HTTP_NO_CONTENT;
        }
        return $this->jsonResponse($message, self::STATUS_OK, $httpStatues);
    }

    public function jsonResponseErrorServer(
        $message,
        string $httpStatus = Response::HTTP_INTERNAL_SERVER_ERROR
    ): JsonResponse {
        return $this->jsonResponse($message, self::STATUS_ERROR, $httpStatus);
    }

    public function jsonResponseErrorClient(
        $message,
        int $httpStatus = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return $this->jsonResponse($message, self::STATUS_ERROR, $httpStatus);
    }
}