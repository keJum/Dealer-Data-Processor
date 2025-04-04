<?php

namespace App\Validators;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class TokenByResetConstraint extends Constraint
{
    public string $message = 'Переданный токен имеет тип: {{ string }} и не является токеном для сброса пароля';

    public function validatedBy(): string
    {
        return TokenByResetValidator::class;
    }
}