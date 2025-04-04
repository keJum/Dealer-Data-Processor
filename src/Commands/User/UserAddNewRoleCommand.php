<?php

namespace App\Commands\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserAddNewRoleCommand extends Command
{
    protected static $defaultName = 'user:add-role';

    private UserRepository $userRepository;

    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->entityManager  = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $roles = User::getAllRoles();
        $roles = implode(' ', $roles);
        $this->setDescription("Назначение пользователю новой роли: $roles");
        $this->addArgument(
            'email',
            InputArgument::REQUIRED,
            "Электронный почтовый адрес пользователя"
        );
        $this->addArgument(
            'role',
            InputArgument::REQUIRED,
            "Новая роль пользователя: $roles"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = Command::SUCCESS;
        try {
            $user = $this->userRepository->findOneBy(['email' => $input->getArgument('email')]);
            if($user === null) {
                throw new RuntimeException("Не удалось найти пользователя");
            }
            $user->addRole($input->getArgument('role'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $output->writeln("Пользователю была записана новая роль");
        } catch (RuntimeException $exception) {
            $output->writeln($exception->getMessage());
            $result = Command::FAILURE;
        }
        return $result;
    }
}