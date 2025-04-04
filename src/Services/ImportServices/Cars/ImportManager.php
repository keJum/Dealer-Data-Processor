<?php

namespace App\Services\ImportServices\Cars;

use App\Commands\Exceptions\NotFoundStrategyException;
use App\Repository\CarAttributeRepository;
use Doctrine\ORM\EntityManagerInterface;

class ImportManager
{
    public const EXTENSION_JSON = 'json';
    public const EXTENSION_XML = 'xml';
    public const EXTENSION_YAML = 'yaml';
    public const EXTENSION_CSV = 'csv';

    private EntityManagerInterface $entityManager;
    private CarAttributeRepository $carAttributeRepository;

    public function __construct(EntityManagerInterface $entityManager, CarAttributeRepository $carAttributeRepository)
    {
        $this->entityManager = $entityManager;
        $this->carAttributeRepository = $carAttributeRepository;
    }

    /**
     * @throws NotFoundStrategyException
     */
    public function createImportByFileExtension(string $fileExtension): Importer
    {
        switch ($fileExtension){
            case self::EXTENSION_JSON:
                $importer = new JsonImport($this->entityManager, $this->carAttributeRepository);
                break;
            case self::EXTENSION_XML:
                $importer = new XmlImport($this->entityManager, $this->carAttributeRepository);
                break;
            case self::EXTENSION_YAML:
                $importer = new YamlImport($this->entityManager, $this->carAttributeRepository);
                break;
            case self::EXTENSION_CSV:
                $importer = new CsvImport($this->entityManager, $this->carAttributeRepository);
                break;
            default:
                throw new NotFoundStrategyException;
        }
        return $importer;
    }
}