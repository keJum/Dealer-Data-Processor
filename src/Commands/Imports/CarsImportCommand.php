<?php

namespace App\Commands\Imports;

use App\Commands\Exceptions\NotFoundFileException;
use App\Services\ImportServices\Cars\ImportManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CarsImportCommand extends Command
{
    protected static $defaultName = "import:cars";
    private Finder $finder;
    private string $importFolder;
    private LoggerInterface $logger;
    private Filesystem $filesystem;
    private ImportManager $importManager;

    public function __construct(
        string $name = null,
        string $importFolder,
        LoggerInterface $logger,
        Filesystem $filesystem,
        ImportManager $importManager
    ) {
        parent::__construct($name);
        $this->finder = new Finder();
        $this->importFolder = $importFolder;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->importManager = $importManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->finder->files()->in($this->importFolder);
        try {
            if (!$this->finder->hasResults()) {
                throw new NotFoundFileException;
            }
            $this->finder->sortByModifiedTime();
            foreach ($this->finder->files() as $file) {
                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

                $this->importManager->createImportByFileExtension($fileExtension)->import($file->getContents());

                $this->filesystem->remove($file);
            }
            $result = Command::SUCCESS;
        } catch (Exception $exception) {
            $this->logger->error($exception);
            $result = Command::FAILURE;
        }
        return $result;
    }
}