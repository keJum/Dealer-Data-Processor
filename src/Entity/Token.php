<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TokenRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Token
{
    public const TYPE_AUTH = 'auth';
    public const TYPE_RESET = 'reset';

    private const TYPE = [
        self::TYPE_AUTH,
        self::TYPE_RESET
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $type;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private string $value;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $is_lifetime;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $lifetime;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="token")
     */
    private ?User $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    private function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getIsLifetime(): ?bool
    {
        return $this->is_lifetime;
    }

    public function setIsLifetime(bool $is_lifetime): self
    {
        $this->is_lifetime = $is_lifetime;

        return $this;
    }

    public function getLifetime(): ?DateTimeInterface
    {
        return $this->lifetime;
    }

    public function setLifetime(?DateTimeInterface $lifetime): self
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public static function createToken(string $primacy = null): string
    {
        return md5(uniqid($primacy ?? '' . time(), true));
    }

    public function setTokenAuth(string $primacy = null): self
    {
        $this->setValue(self::createToken($primacy));
        $this->setType(self::TYPE_AUTH);
        $this->setIsLifetime(false);
        return $this;
    }

    public function setTokenReset(string $primacy = null): self
    {
        $tokenLifeTimeStart = new DateTime();
        $tokenLifeTimeEnd = $tokenLifeTimeStart->add(new DateInterval("PT3H"));

        $this->setValue(self::createToken($primacy));
        $this->setType(self::TYPE_RESET);
        $this->includeLifetime($tokenLifeTimeEnd);
        return $this;
    }

    public function includeLifetime(DateTimeInterface $dateTime): void
    {
        $this->setIsLifetime(true);
        $this->setLifetime($dateTime);
    }

    public function isActualLifetime(): bool
    {
        $isActual = true;
        $isLifetime = $this->getLifetime();
        $lifetime = $this->getLifetime();
        $currentTime = new DateTime();
        if ($isLifetime && ($lifetime === null || $lifetime->getTimestamp() < $currentTime->getTimestamp())) {
            $isActual = false;
        }
        return $isActual;
    }

}
