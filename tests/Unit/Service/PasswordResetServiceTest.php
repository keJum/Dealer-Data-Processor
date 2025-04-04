<?php

namespace App\Tests\Unit\Service;

use App\Entity\Token;
use App\Fixtures\UserFixture;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use App\Services\UserServices\Dto\PasswordResetDto;
use App\Services\UserServices\Dto\RegistrationDto;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenAuthNotFoundException;
use App\Services\UserServices\Exceptions\PasswordResetServiceExceptions\TokenResetIsActualException;
use App\Services\UserServices\Interfaces\PasswordResetServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class PasswordResetServiceTest extends KernelTestCase
{
    /**
     * @var PasswordResetDto|MockObject
     */
    private $passwordResetDto;

    private ?PasswordResetServiceInterface $service;
    private ?RegistrationDto $dto;
    private ?UserRepository $userRepository;
    private ?UserPasswordEncoderInterface $userPasswordEncoder;
    private ?TokenRepository $tokenRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(PasswordResetServiceInterface::class);
        $this->userRepository = self::$container->get(UserRepository::class);
        $this->tokenRepository = self::$container->get(TokenRepository::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::$container->get(UserFixture::class)->load($entityManager);

        $this->passwordResetDto = $this->getMockBuilder(PasswordResetDto::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser'])
            ->getMock();

    }


    /**
     * @throws EntityNotFoundException
     * @throws TokenAuthNotFoundException
     * @throws TokenResetIsActualException
     * @throws TransportExceptionInterface
     */
    public function testInvoke(): void
    {
        $this->expectNotToPerformAssertions();
        $user = $this->userRepository->findOneBy(['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED]);
        $this->passwordResetDto->method('getUser')->willReturn($user);

        $service = $this->service;
        $service($this->passwordResetDto);

        // Проверяем что был создан токен для восстановления пароля
        $user->getTokenReset();
    }

    /**
     * @throws TokenAuthNotFoundException
     * @throws TokenResetIsActualException
     * @throws TransportExceptionInterface
     */
    public function testInvokeTokenActual(): void
    {
        $user = $this->userRepository->findOneBy(['email' => UserFixture::EMAIL_USER_CONFIRMATION_AND_ACTIVATED]);

        $tokenReset = new Token();
        $tokenReset->setTokenReset();
        $this->entityManager->persist($tokenReset);

        $user->addToken($tokenReset);
        $this->entityManager->persist($user);

        $this->entityManager->flush();
        $this->passwordResetDto->method('getUser')->willReturn($user);

        $service = $this->service;
        $this->expectException(TokenResetIsActualException::class);
        $service($this->passwordResetDto);
    }
}
