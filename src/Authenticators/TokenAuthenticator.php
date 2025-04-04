<?php

namespace App\Authenticators;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        $data = array(
            'type' => 'error',
            'message' => 'Не найдено поле X-AUTH-TOKEN'
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    public function getCredentials(Request $request): string
    {
        return $request->headers->get('X-AUTH-TOKEN');
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        if ($credentials === null) {
            $user = null;
        } else {
            $user = $this->userRepository->findByAuthToken($credentials);
            if ($user !== null) {
                $user = $user->getIsActivated() ? $user : null;
            }
        }
        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $data = array(
            'type' => 'error',
            'message' => 'Не верный X-AUTH-TOKEN'
        );

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?bool
    {
        return null;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    public static function createApiToken(User $user): string
    {
        $token = Token::createToken($user->getEmail());
        return md5($token);
    }
}