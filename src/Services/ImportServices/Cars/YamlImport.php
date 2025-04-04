<?php

namespace App\Services\ImportServices\Cars;

use Symfony\Component\Yaml\Yaml;

class YamlImport extends Importer
{
    public function import(string $content): void
    {
        $data = Yaml::parse($content);
        $this->setCarAndCarAttributeByArray($data);
    }
}