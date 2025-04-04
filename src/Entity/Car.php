<?php

namespace App\Entity;

use App\Repository\CarsRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CarsRepository::class)
 */
class Car
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", unique=true)
     */
    private int $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTimeInterface $created_at;

    /**
     * @ORM\OneToMany(targetEntity=CarAttribute::class, mappedBy="car", orphanRemoval=true)
     */
    private Collection $carAttributes;

    public function __construct()
    {
        $this->carAttributes = new ArrayCollection();
        $this->setCreatedAt(new DateTime());
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection|CarAttribute[]
     */
    public function getCarAttributes(): Collection
    {
        return $this->carAttributes;
    }

    public function addCarAttribute(CarAttribute $carAttribute): self
    {
        if (!$this->carAttributes->contains($carAttribute)) {
            $this->carAttributes[] = $carAttribute;
            $carAttribute->setCar($this);
        }

        return $this;
    }

    public function removeCarAttribute(CarAttribute $carAttribute): self
    {
        // set the owning side to null (unless already changed)
        if ($this->carAttributes->removeElement($carAttribute) && $carAttribute->getCar() === $this) {
            $carAttribute->setCar(null);
        }

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->created_at;
    }

    private function setCreatedAt(DateTimeInterface $created_at): void
    {
        $this->created_at = $created_at;
    }
}
