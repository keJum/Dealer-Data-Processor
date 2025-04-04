<?php

namespace App\Commands\User;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActivateUserCommand extends Command
{
    protected static $defaultName = "user:activate";

    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private bool $isActivity = true;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            "email",
            InputArgument::REQUIRED,
            "Электронный почтовый адрес пользователя"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = self::SUCCESS;
        try {
            $user = $this->userRepository->findOneBy(['email' => $input->getArgument("email")]);
            if ($user === null) {
                throw new RuntimeException("Не найден пользователь");
            }
            $user->setIsActivated(true);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $output->writeln( "Пользователь активирован");
        } catch (RuntimeException $exception) {
            $output->writeln($exception->getMessage());
            $result = self::FAILURE;
        }

        return $result;
    }
}