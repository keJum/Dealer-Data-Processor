<?php

namespace App\Services\ImportServices\Cars;

use App\Entity\Car;
use App\Entity\CarAttribute;
use Exception;
use SimpleXMLElement;

class XmlImport extends Importer
{
    /**
     * @throws Exception
     */
    public function import(string $content): void
    {
        $data = simplexml_load_string($content);
        if ($data === false) {
            throw new Exception();
        }
        foreach ($data as $attributes) {
            $carId = $attributes->{'id'};
            $carAttributeEntity = $this->carAttributeRepository->findOneBy(['name' => 'id', 'value' => $carId]);
            if ($carAttributeEntity !== null && ($carEntity = $carAttributeEntity->getCar()) !== null) {
                $this->deleteAttributeByCar($carEntity);
            } else {
                $carEntity = new Car();
                $this->entityManager->persist($carEntity);
            }
            $this->entityManager->flush();
            foreach ($attributes as $attribute => $value) {
                $this->setAttribute($carEntity, $attribute, $value);
            }
        }
    }

    /**
     * @param string|SimpleXMLElement $value
     */
    protected function setAttribute(Car $carEntity, string $attribute, $value): CarAttribute
    {
        $attributeEntity = new CarAttribute($carEntity, $attribute);
        if ($value->count() > 1) {
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

}