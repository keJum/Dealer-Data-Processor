<?php

namespace App\Services\AdminServices\Dto;

use App\Dto\Exceptions\ValidateDtoWarningException;
use App\Dto\ValidateDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserDto extends ValidateDto
{
    private User $user;
    private UserRepository $userRepository;

    public function __construct(
        ValidatorInterface $validator,
        UserRepository $userRepository
    ) {
        parent::__construct($validator);
        $this->userRepository = $userRepository;
    }

    /**
     * @throws ValidateDtoWarningException
     */
    public function setByEmail(string $email): void
    {
        $this->validateBy(new Assert\Email(['message' => 'Поле email не валидное']), $email);

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user === null) {
            throw new ValidateDtoWarningException("Не найден пользователь с такой электронной почтой");
        }
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}