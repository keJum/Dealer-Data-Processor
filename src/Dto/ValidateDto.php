<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraint;
use App\Dto\Exceptions\ValidateDtoWarningException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class ValidateDto
{
    protected ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function validate(): ?ConstraintViolationListInterface
    {
        return $this->validator->validate($this);
    }

    /**
     * @throws ValidateDtoWarningException
     */
    public function validateOrFail(): void
    {
        $this->errorToException($this->validate());
    }

    /**
     * @throws ValidateDtoWarningException
     */
    protected function validateBy(Constraint $constraint, $value): void
    {
        $this->errorToException($this->validator->validate(
            $value,
            $constraint
        ));
    }

    /**
     * @throws ValidateDtoWarningException
     */
    private function errorToException(?ConstraintViolationListInterface $errors, ?string $message = null): void
    {
        if ($errors !== null && count($errors) > 0) {
            throw new ValidateDtoWarningException($message ?? ($errors->get(0)->getMessage() .
                'property: ' . $errors->get(0)->getPropertyPath()
            ));
        }
    }
}