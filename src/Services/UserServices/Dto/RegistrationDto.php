<?php

namespace App\Services\UserServices\Dto;

use App\Dto\Exceptions\ValidateDtoWarningException;
use App\Dto\ValidateDto;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationDto extends ValidateDto
{
    /**
     * @Assert\NotBlank(
     *     message="Поле email обязательная для заполнения"
     * )
     * @Assert\Email(
     *     message="Поле email не валидное"
     * )
     */
    private string $email;
    /**
     * @Assert\NotBlank(
     *     message="Поле password обязательная для заполнения"
     * )
     */
    private string $password;

    /**
     * @throws ValidateDtoWarningException
     */
    public function setByEmailAndPassword(string $email, string $password): void
    {
        $this->email = $email;
        $this->password = $password;

        $this->validateOrFail();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}