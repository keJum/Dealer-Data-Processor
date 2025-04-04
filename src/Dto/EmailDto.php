<?php

namespace App\Dto;

use App\Dto\Exceptions\ValidateDtoWarningException;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class EmailDto extends ValidateDto
{
    /**
     * @Assert\Email
     * @Assert\NotBlank
     */
    private string $fromEmail;
    /**
     * @Assert\Email
     * @Assert\NotBlank
     */
    private string $toEmail;
    /**
     * @Assert\NotBlank
     */
    private string $subject;
    /**
     * @Assert\NotBlank
     */
    private string $text;

    /**
     * @throws Exceptions\ValidateDtoWarningException
     */
    public function setRequest(Request $request): self
    {
        $json = $request->toArray();
        if (
            !is_array($json) ||
            !array_key_exists('subject', $json) ||
            !array_key_exists('text', $json)
        ) {
            throw new ValidateDtoWarningException('Не верный запрос');
        }
        $subject = $request->toArray()['subject'];
        $text = $request->toArray()['text'];

        $this->subject = $subject;
        $this->text = $text;

        return $this;
    }

    public function setToEmail(string $toEmail): void
    {
        $this->toEmail = $toEmail;
    }

    public function setFromEmail(string $fromEmail): void
    {
        $this->fromEmail = $fromEmail;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @throws JsonException
     * @throws ValidateDtoWarningException
     */
    public function getJson(): string
    {
        $this->validateOrFail();
        return json_encode(
            [
                'fromEmail' => $this->fromEmail,
                'toEmail' => $this->toEmail,
                'subject' => $this->subject,
                'text' => $this->text
            ],
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @throws JsonException
     */
    public function setJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->fromEmail = $data['fromEmail'];
        $this->toEmail = $data['toEmail'];
        $this->subject = $data['subject'];
        $this->text = $data['text'];

        return $this;
    }
}