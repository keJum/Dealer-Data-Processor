<?php

namespace App\Services\ImportServices\Cars;

use App\Entity\Car;
use JsonException;

class JsonImport extends Importer
{
    /**
     * @throws JsonException
     */
    public function import(string $content): void
    {
        $this->setCarAndCarAttributeByArray(
            json_decode($content, true, 512, JSON_THROW_ON_ERROR)
        );
    }
}