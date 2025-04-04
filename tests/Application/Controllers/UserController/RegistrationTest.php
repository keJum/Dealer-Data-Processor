<?php

namespace App\Tests\Application\Controllers\UserController;

use App\Entity\Token;
use App\Entity\User;
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
 * @covers \App\Controller\UserController::registration
 */
class RegistrationTest extends DefaultControllerTest
{
    protected ?UserRepository $userRepository;
    protected ?TokenRepository $tokenRepository;
    protected string $email;
    protected string $password;
    protected ?EntityManager $entityManager;
    protected ?UserFixture $userFixture;
    private ?UserPasswordEncoderInterface $userPasswordEncoder;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = static::$container->get(UserRepository::class);
        $this->tokenRepository = static::$container->get(TokenRepository::class);
        $this->entityManager = static::$container->get(EntityManagerInterface::class);
        $this->userFixture = static::$container->get(UserFixture::class);
        $this->userPasswordEncoder = self::$container->get(UserPasswordEncoderInterface::class);

        $this->email = "test@gmail.com";
        $this->password = '123';
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testRegister(): void
    {
        $response = static::createClient()->request('POST', '/user/registration', [
            'json' => [
                'email' => $this->email,
                'password' => $this->password
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseJson($response, "На электронную почту отправлено сообщение для подтверждения");

        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $this->email]);
        $this->assertIsUserCreated(true, $user, 'Пользователь не был создан', $this->password);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testRegisterEmailNotUnique(): void
    {
        $this->userFixture->load($this->entityManager);
        static::createClient()->request('POST', '/user/registration', [
            'json' => [
                'email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED,
                'password' => $this->password
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testRegisterEmailNotValue(): void
    {
        $emailNotValidate = "test.com";
         static::createClient()->request('POST', '/user/registration', [
            'json' => [
                'email' => $emailNotValidate,
                'password' => $this->password
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $this->email]);
        $this->assertIsUserCreated(false, $user, 'Пользователь был создан');
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testRegisterEmailNotBlank(): void
    {
        static::createClient()->request('POST', '/user/registration', [
            'json' => [
                'email' => '',
                'password' => $this->password
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testRegisterPasswordNotBlank(): void
    {
        static::createClient()->request('POST', '/user/registration', [
            'json' => [
                'email' => $this->email,
                'password' => ''
            ]
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $this->email]);
        $this->assertIsUserCreated(false, $user, 'Пользователь был создан');
    }

    private function assertIsUserCreated(
        bool $isCreated,
        ?User $user,
        string $message,
        string $password = ''
    ): void {
        if ($isCreated) {
            $this->assertNotNull($user, $message);
            $this->assertFalse(
                $user->isHaveRole(User::IS_AUTHENTICATED_FULLY),
                "У зарегистрирована пользователя подтверждена почта при регистрации"
            );
            $this->assertTrue(
                $user->isHaveRole(User::ROLE_USER),
                "У зарегистрирована пользователя при регистрации не была добавлена роль: ".User::ROLE_USER
            );

            $this->assertTrue(
                $this->userPasswordEncoder->isPasswordValid($user, $password),
                'Пользователю был установлен не верный пароль'
            );

            /** @var Token $token */
            $token = $this->tokenRepository->findOneBy(['user' => $user, 'type' => Token::TYPE_AUTH]);
            $this->assertNotNull($token, "Токен подтверждения пароля не был создан");
        } else {
            $this->assertNull($user, $message);
        }
    }
}