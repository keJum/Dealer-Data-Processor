<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Fixtures\UserFixture;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use App\Services\UserServices\Dto\RegistrationDto;
use App\Services\UserServices\Exceptions\RegistrationServiceException;
use App\Services\UserServices\Exceptions\RegistrationServiceExceptions\EmailNotUniqueException;
use App\Services\UserServices\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @covers \App\Services\UserServices\RegistrationService::__invoke
 */
class RegistrationServiceTest extends KernelTestCase
{
    /**
     * @var RegistrationDto|MockObject
     */
    private $registrationDtoMock;
    private ?RegistrationService $service;
    private ?RegistrationDto $dto;
    private ?UserRepository $userRepository;
    private ?UserPasswordEncoderInterface $userPasswordEncoder;
    private ?TokenRepository $tokenRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(RegistrationService::class);
        $this->userRepository = self::$container->get(UserRepository::class);
        $this->tokenRepository = self::$container->get(TokenRepository::class);
        $this->userPasswordEncoder = self::$container->get(UserPasswordEncoderInterface::class);

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::$container->get(UserFixture::class)->load($entityManager);

        $this->registrationDtoMock = $this->getMockBuilder(RegistrationDto::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEmail', 'getPassword'])
            ->getMock();
    }

    /**
     * @throws EmailNotUniqueException
     * @throws RegistrationServiceException
     * @throws EntityNotFoundException
     */
    public function testInvoke(): void
    {
        $email = 'test@email.com';
        $password = '123';
        $this->registrationDtoMock->method('getEmail')->willReturn($email);
        $this->registrationDtoMock->method('getPassword')->willReturn($password);

        $service = $this->service;
        $service($this->registrationDtoMock);

        $user = $this->userRepository->findOneBy(['email' => $email]);
        $this->assertNotNull($user, 'Пользователь не был создан');

        $this->assertTrue(
            $this->userPasswordEncoder->isPasswordValid($user, $password),
            'Неверно установлен пароль'
        );

        // Проверяем что у пользователя создался токен для авторизации
        $user->getAuthToken();

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
    }

    /**
     * @throws RegistrationServiceException
     * @throws EmailNotUniqueException
     */
    public function testInvokeEmailNtoUnique(): void
    {
        $email = UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED;
        $password = '123';
        $this->registrationDtoMock->method('getEmail')->willReturn($email);
        $this->registrationDtoMock->method('getPassword')->willReturn($password);

        $service = $this->service;
        $this->expectException(EmailNotUniqueException::class);
        $service($this->registrationDtoMock);
    }

}
