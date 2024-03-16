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

        $totalInterest = 0;
        foreach ($files as $filename) {
            echo "$filename\n" . str_repeat('-', strlen($filename)) . "\n";

            $fileType = $this->getFileType($filename);
            $fullFilename = $inputFolder . '/' . $filename;
            $fileData = $this->fileReaders[$fileType]->getInterestData($fullFilename);

            $fileInterest = 0;
            foreach ($fileData as $transaction) {
                echo $transaction['date']->format('Y-m-d') . ': ' . $transaction['interest'] . "\n";
                $fileInterest += $transaction['interest'];
            }

            echo "File total: $fileInterest\n\n";
            $totalInterest += $fileInterest;
        }

        echo "GRAND TOTAL: $totalInterest\n";
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
