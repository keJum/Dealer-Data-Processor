<?php

namespace App\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityNotFoundException;
use App\Entity\Token as TokenEntity;
use Exception;

trait Token
{
    abstract public function addToken(TokenEntity $token): self;

    abstract public function getTokens(): Collection;

    abstract public function removeToken(TokenEntity $token);

    /**
     * @throws EntityNotFoundException|Exception
     */
    private function getTokensByType(string $type): Collection
    {
        $tokensType = new ArrayCollection();
        $tokens = $this->getTokens();
        if ($tokens->isEmpty()) {
            throw new EntityNotFoundException();
        }
        foreach ($tokens->getIterator() as $token) {
            /** @var $token TokenEntity */
            if ($token->getType() === $type ) {
                $tokensType->add($token);
            }
        }
        if ($tokensType->isEmpty()) {
            throw new EntityNotFoundException();
        }

        return $tokensType;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getTokensAuth(): Collection
    {
        return $this->getTokensByType(TokenEntity::TYPE_AUTH);
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getAuthToken(): TokenEntity
    {
        return $this->getTokensAuth()->last();
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getTokensReset(): Collection
    {
        return $this->getTokensByType(TokenEntity::TYPE_RESET);
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getTokenReset(): TokenEntity
    {
        return $this->getTokensReset()->last();
    }

    public function isResetTokenActual(): bool
    {
        $isResetTokenActual = false;
        try {
            $token = $this->getTokenReset();
            if ($token !== null) {
                $isResetTokenActual = $token->isActualLifetime();
            }
        } catch (EntityNotFoundException $e) {
            $isResetTokenActual = false;
        }
        return $isResetTokenActual;
    }
}