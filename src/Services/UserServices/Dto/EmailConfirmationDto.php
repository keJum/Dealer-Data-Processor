<?php

namespace App\Services\UserServices\Dto;

use App\Dto\Exceptions\ValidateDtoErrorException;
use App\Dto\Exceptions\ValidateDtoWarningException;
use App\Dto\ValidateDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailConfirmationDto extends ValidateDto
{
    private User $user;
    private UserRepository $userRepository;

    public function __construct(ValidatorInterface $validator, UserRepository $userRepository)
    {
        parent::__construct($validator);
        $this->userRepository = $userRepository;
    }

    /**
     * @throws ValidateDtoErrorException
     */
    public function setByUser(UserInterface $user): void
    {
        if (!$user instanceof User) {
            throw new ValidateDtoErrorException('Переменная $user не является классом типа: ' . User::class);
        }

        $this->user = $user;
    }

    /**
     * @throws ValidateDtoWarningException
     */
    public function setByEmailUser(?string $email): void
    {
        if (empty($email)) {
            throw new ValidateDtoWarningException('Поле email обязательное для заполнения');
        }
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user === null) {
            throw new ValidateDtoWarningException("Пользователь с таким email не найден");
        }
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}