<?php

namespace App\Services\ImportServices\Cars;

use App\Entity\Car;

class CsvImport extends Importer
{
    public function import(string $content): void
    {
        $data = $this->csvstring_to_array($content);
        $dataNew = [];
        for ($i = 1, $iMax = count($data); $i < $iMax; $i++) {
            $dataNew[$i] = [];
            for ($a = 0, $aMax = count($data[0]); $a < $aMax; $a++) {
                $columnNames = $data[0][$a];
                $rowData = $data[$i][$a];
                $columnNames = explode('/',$columnNames);
                $array = $this->recursiveSetArray($columnNames, $rowData);

                $dataNew[$i] = array_merge_recursive($dataNew[$i], $array);
            }
        }
        $this->setCarAndCarAttributeByArray($dataNew);
    }

    private function recursiveSetArray(array $columnNames, string $rowValue): array {
        if (count($columnNames) > 1) {
            $columnName = array_shift($columnNames);
            return [$columnName => $this->recursiveSetArray($columnNames, $rowValue)];
        }
        return [$columnNames[0] => $rowValue];
    }

    /*
     * @author: Klemen Nagode
     * @links https://stackoverflow.com/a/4975728/9136209
     */
    private function csvstring_to_array($string): array
    {
        $array = array();
        $size = strlen($string);
        $columnIndex = 0;
        $rowIndex = 0;
        $fieldValue = "";
        $isEnclosured = false;
        for ($i = 0; $i < $size; $i++) {
            $char = $string[$i];
            $addChar = "";

            if ($isEnclosured) {
                if ($char === '"') {
                    if ($i + 1 < $size && $string[$i + 1] === '"') {
                        // escaped char
                        $addChar = $char;
                        $i++; // dont check next char
                    } else {
                        $isEnclosured = false;
                    }
                } else {
                    $addChar = $char;
                }
            } elseif ($char === '"') {
                $isEnclosured = true;
            } elseif ($char === ',') {
                $array[$rowIndex][$columnIndex] = $fieldValue;
                $fieldValue = "";

                $columnIndex++;
            } elseif ($char === "\n") {
                echo $char;
                $array[$rowIndex][$columnIndex] = $fieldValue;
                $fieldValue = "";
                $columnIndex = 0;
                $rowIndex++;
            } else {
                $addChar = $char;
            }
            if ($addChar != "") {
                $fieldValue .= $addChar;
            }
        }

        if ($fieldValue) { // save last field
            $array[$rowIndex][$columnIndex] = $fieldValue;
        }
        return $array;
    }


    protected function setCarAndCarAttributeByArray(array $data): void
    {
        foreach ($data as $attributes) {
            $carId = $attributes['id'];
            $carAttributeEntity = $this->carAttributeRepository->findOneBy(['name' => 'id', 'value' => $carId]);
            if ($carAttributeEntity !== null && ($carEntity = $carAttributeEntity->getCar()) !== null) {
                $this->deleteAttributeByCar($carEntity);
            } else {
                $carEntity = new Car();
                $this->entityManager->persist($carEntity);
            }
            foreach ($attributes as $attribute => $value) {
                $this->setAttribute($carEntity, $attribute, $value);
            }
        }
    }

}