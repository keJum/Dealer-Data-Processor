<?php

namespace App\Validators;

use App\Entity\Token;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TokenByResetValidator extends ConstraintValidator
{
    /**
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint): void
    {
        if(!$constraint instanceof TokenByResetConstraint) {
            throw new UnexpectedTypeException($constraint, TokenByResetConstraint::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!$value instanceof Token) {
            throw new UnexpectedTypeException($constraint, Token::class);
        }

        if ($value->getType() !== Token::TYPE_RESET) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value->getType())
                ->addViolation();
        }
    }
}