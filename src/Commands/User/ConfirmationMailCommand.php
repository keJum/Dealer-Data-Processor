<?php

namespace App\Commands\User;

use App\Dto\Exceptions\ValidateDtoWarningException;
use App\Repository\UserRepository;
use App\Services\UserServices\Dto\EmailConfirmationDto;
use App\Services\UserServices\EmailConfirmationService;
use App\Services\UserServices\Exceptions\EmailConfirmationServiceExceptions\EmailBeenConfirmationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ConfirmationMailCommand extends Command
{
    protected static $defaultName = 'user:confirmation-email';

    private UserRepository $userRepository;
    private EmailConfirmationService $userService;
    private EmailConfirmationDto $dto;

    public function __construct(
        EmailConfirmationService $userService,
        EmailConfirmationDto $dto
    )
    {
        $this->userService = $userService;
        $this->dto = $dto;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription("Подтверждение email для учетной записи");
        $this->addArgument(
            'email',
            InputArgument::REQUIRED,
            "Электронный почтовый адрес пользователя"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userService = $this->userService;
        $result = Command::SUCCESS;
        try {
            $email = $input->getArgument('email');
            $this->dto->setByEmailUser($email);
            $userService($this->dto)->sendEmail();
            $output->writeln("Электронная почта подтверждена");
        } catch (EmailBeenConfirmationException $e) {
            $output->writeln("Электронная почта уже подтверждена");
            $result = Command::FAILURE;
        } catch (TransportExceptionInterface $e) {
            $output->writeln("Не удалось отправить сообщение на почту");
            $result = Command::FAILURE;
        } catch (ValidateDtoWarningException $exception) {
            $output->writeln($exception->getMessage());
            $result = Command::FAILURE;
        }
        return $result;
    }
}