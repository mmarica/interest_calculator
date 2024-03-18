<?php

namespace Mma\Interest;

use Exception;
use Mma\Interest\TransactionFile\AbstractFileReader;
use Mma\Interest\TransactionFile\IngCsv;
use Mma\Interest\TransactionFile\RaiffeisenXlsx;

class InterestReport
{
    private array $fileReaders = [];
    private array $filePatterns = [];

    public function __construct()
    {
        $this->registerFileReaders([
            IngCsv::class,
            RaiffeisenXlsx::class,
        ]);
    }

    public function generate(string $inputFolder): void
    {
        $files = FileSystem::findByPatternList($inputFolder, $this->filePatterns);

        if (!count($files)) {
            die("No files found.\n");
        }

        $totalInterest = 0;
        foreach ($files as $filename) {
            echo "$filename\n";
            echo str_repeat('-', strlen($filename)) . "\n";

            $fileType = $this->getFileType($filename);
            $fullFilename = $inputFolder . '/' . $filename;
            $fileData = $this->fileReaders[$fileType]->getInterestData($fullFilename);

            $fileInterest = 0;
            $transactionCount = 0;
            foreach ($fileData as $transaction) {
                echo $transaction['date']->format('Y-m-d') . ': ' . $transaction['interest'] . "\n";
                $fileInterest += $transaction['interest'];
                $transactionCount++;
            }

            if (!$transactionCount) {
                echo "no transaction identified\n";
            }

            echo str_repeat('-', strlen($filename)) . "\n";
            echo "File total: $fileInterest\n\n";
            $totalInterest += $fileInterest;
        }

        echo "GRAND TOTAL: $totalInterest\n";
    }

    /**
     * @param AbstractFileReader[] $fileReaderClasses
     */
    private function registerFileReaders(array $fileReaderClasses): void
    {
        foreach ($fileReaderClasses as $fileReaderClass) {
            $this->fileReaders[$fileReaderClass::FILE_TYPE] = new $fileReaderClass();

            $this->filePatterns[] = $fileReaderClass::FILE_PATTERN;
        }

        $this->filePatterns = array_unique($this->filePatterns);
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
