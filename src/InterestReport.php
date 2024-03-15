<?php

namespace Mma\Interest;

use Exception;
use Mma\Interest\TransactionFile\IngCsv;

class InterestReport
{
    private array $fileReaders;

    public function __construct()
    {
        $this->fileReaders = [
            IngCsv::FILE_TYPE => new IngCsv(),
        ];
    }

    public function generate(string $inputFolder): void
    {
        $files = FileSystem::findByExtension($inputFolder, 'csv');
        var_dump(FileSystem::findByExtension($inputFolder, 'csv'));

        $filesData = [];
        foreach ($files as $filename) {
            $fileType = $this->getFileType($filename);
            $fullFilename = $inputFolder . '/' . $filename;
            $filesData[$filename] = $this->fileReaders[$fileType]->getInterestData($fullFilename);
        }

        print_r($filesData);
    }

    private function getFileType(string $filename): string
    {
        foreach ($this->fileReaders as $fileType => $fileReader) {
            if ($fileReader->isFileThisType($filename)) {
                return $fileType;
            }
        }

        throw new Exception("Unknown file type for '$filename'.");
    }
}
