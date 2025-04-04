<?php

namespace App\Services\AdminServices;

use App\Repository\UserRepository;
use App\Services\AdminServices\Interfaces\UserListServiceInterface;

class UserListService implements UserListServiceInterface
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function list(): array
    {
        $users = $this->userRepository->findAll();
        return [
            'emails' => array_map(static function ($user) {
                return $user->getEmail();
            }, $users)
        ];
    }
}