<?php

namespace App\Services\ImportServices\Cars;

use App\Entity\Car;
use App\Entity\CarAttribute;
use App\Repository\CarAttributeRepository;
use Doctrine\ORM\EntityManagerInterface;

abstract class Importer
{
    protected EntityManagerInterface $entityManager;
    protected CarAttributeRepository $carAttributeRepository;

    public function __construct(EntityManagerInterface $entityManager, CarAttributeRepository $carAttributeRepository)
    {
        $this->entityManager = $entityManager;
        $this->carAttributeRepository = $carAttributeRepository;
    }

    protected function setCarAndCarAttributeByArray(array $data): void
    {
        foreach ($data as $attributes) {
            $carId = $attributes[0]['id'];
            $carAttributeEntity = $this->carAttributeRepository->findOneBy(['name' => 'id', 'value' => $carId]);

            if ($carAttributeEntity !== null && ($carEntity = $carAttributeEntity->getCar()) !== null) {
                $this->deleteAttributeByCar($carEntity);
            } else {
                $carEntity = new Car();
                $this->entityManager->persist($carEntity);
            }

//            if ($carAttributeEntity === null || ($carEntity = $carAttributeEntity->getCar()) === null) {
//                $carEntity = new Car();
//                $this->entityManager->persist($carEntity);
//            }
            foreach ($attributes[0] as $attribute => $value) {
                $this->setAttribute($carEntity, $attribute, $value);
            }
        }
    }

    /**
     * @param string|array $value
     */
    protected function setAttribute(Car $carEntity, string $attribute, $value): CarAttribute
    {
        $attributeEntity = new CarAttribute($carEntity, $attribute);

//        $attributeEntity = $this->carAttributeRepository->findOneBy([
//            'name' => $attribute,
//            'car' => $carEntity
//        ]);
//        if ($attributeEntity === null) {
//            $attributeEntity = new CarAttribute($carEntity, $attribute);
//        }

        if (is_array($value)) {
            foreach ($value as $subAttribute => $subValue) {
                $attributeEntityNew = $this->setAttribute($carEntity, $subAttribute, $subValue);
                $attributeEntity->setValue(null);
                $this->entityManager->persist($attributeEntity);
                $this->entityManager->flush();
                $attributeEntity->addCarAttribute($attributeEntityNew);
            }
        } else {
            foreach ($attributeEntity->getCarAttributes() as $subAttributesEntity) {
                $this->entityManager->remove($subAttributesEntity);
            }
            $attributeEntity->setValue($value);
            $this->entityManager->persist($attributeEntity);
            $this->entityManager->flush();
        }
        return $attributeEntity;
    }

    protected function deleteAttributeByCar(Car $car): void
    {
        foreach ($car->getCarAttributes() as $attribute) {
            $this->deleteAttribute($attribute);
        }
    }

    private function deleteAttribute(CarAttribute $carAttribute): void
    {
        if ($subAttributes = $carAttribute->getCarAttributes()) {
            foreach ($subAttributes as $subAttribute) {
                $this->deleteAttribute($subAttribute);
            }
        }
        $this->entityManager->remove($carAttribute);
    }
}