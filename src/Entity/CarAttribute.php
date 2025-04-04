<?php

namespace App\Entity;

use App\Repository\CarAttributeRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CarAttributeRepository::class)
 */
class CarAttribute
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $value;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTimeInterface $created_at;

    /**
     * @ORM\ManyToOne(targetEntity=Car::class, inversedBy="carAttributes")
     * @ORM\JoinColumn(nullable=false)
     */
    private Car $car;

    /**
     * @ORM\ManyToOne(targetEntity=CarAttribute::class, inversedBy="carAttributes", cascade="persist")
     */
    private ?CarAttribute $parent_attribute;

    /**
     * @ORM\OneToMany(targetEntity=CarAttribute::class, mappedBy="parent_attribute")
     */
    private Collection $carAttributes;

    public function __construct(Car $car, string $name)
    {
        $this->setCar($car);
        $this->name= $name;

        $this->setCreatedAt(new DateTime());
        $this->carAttributes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

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

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): self
    {
        $this->car = $car;

        return $this;
    }

    public function getParentAttribute(): ?self
    {
        return $this->parent_attribute;
    }

    public function setParentAttribute(?self $parent_attribute): self
    {
        $this->parent_attribute = $parent_attribute;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getCarAttributes(): Collection
    {
        return $this->carAttributes;
    }

    public function addCarAttribute(self $carAttribute): self
    {
        if (!$this->carAttributes->contains($carAttribute)) {
            $this->carAttributes[] = $carAttribute;
            $carAttribute->setParentAttribute($this);
        }

        return $this;
    }

    public function removeCarAttribute(self $carAttribute): self
    {
        // set the owning side to null (unless already changed)
        if ($this->carAttributes->removeElement($carAttribute) && $carAttribute->getParentAttribute() === $this) {
            $carAttribute->setParentAttribute(null);
        }

        return $this;
    }
}
