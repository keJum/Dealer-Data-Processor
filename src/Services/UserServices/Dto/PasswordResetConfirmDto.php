<?php

namespace App\Services\UserServices\Dto;

use App\Dto\Exceptions\ValidateDtoErrorException;
use App\Dto\Exceptions\ValidateDtoWarningException;
use App\Dto\ValidateDto;
use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Validators as UserAssert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PasswordResetConfirmDto extends ValidateDto
{
    /**
     * @Assert\NotBlank(
     *     message="Поле пароль обязательно для заполнения"
     * )
     */
    private string $password;

    /**
     * @Assert\NotBlank(
     *     message="Поле пароль обязательно для заполнения"
     * )
     */
    private User $user;

    /**
     * @UserAssert\TokenByResetConstraint
     */
    private Token $token;
    private TokenRepository $tokenRepository;

    public function __construct(ValidatorInterface $validator, TokenRepository $tokenRepository)
    {
        parent::__construct($validator);
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * @throws ValidateDtoErrorException
     * @throws ValidateDtoWarningException
     */
    public function setByPasswordAndUserAndToken(?string $password, ?UserInterface $user, ?string $resetToken): void
    {
        $this->validateBy(new Assert\NotBlank(['message' => 'Не найде токен сброса пароля']), $resetToken);
        $token = $this->tokenRepository->findOneBy(['value' => $resetToken]);
        if ($token === null) {
            throw new ValidateDtoWarningException("Введен не верный токен для сброса пароля");
        }
        $this->token = $token;

        if (!$user instanceof User) {
            throw new ValidateDtoErrorException('Объект $user не является классом: '.User::class);
        }
        $this->user = $user;

        if (empty($password)) {
            throw new ValidateDtoWarningException('Поле password обязательное для заполнения');
        }
        $this->password = $password;

        $this->validateOrFail();
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getToken(): Token
    {
        return $this->token;
    }
}