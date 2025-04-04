<?php

namespace App\Entity\Traits;

use RuntimeException;

trait Role
{
    abstract public function setRoles(array $roles): void;

    abstract public function getRoles(): array;

    abstract public static function getAllRoles(): array;

    public function addRole(string $role): self
    {
        $this->inRoleOfModel($role);
        if (in_array($role, $this->getRoles(), true)) {
            throw new RuntimeException("У пользователя уже есть данная роль.");
        }
        $this->setRoles(array_merge($this->getRoles(), [$role]));
        return $this;
    }

    public function isHaveRole(string $role): bool
    {
        $this->inRoleOfModel($role);
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * @throws RuntimeException
     */
    public function inRoleOfModel(string $role): void
    {
        if (!in_array($role, self::getAllRoles(), true)) {
            throw new RuntimeException("Переданная роль не найдена.");
        }
    }
}