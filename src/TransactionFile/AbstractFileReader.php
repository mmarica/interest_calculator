<?php

namespace Mma\Interest\TransactionFile;

use Exception;
use DateTimeImmutable;

abstract class AbstractFileReader
{
    // override with correct values in extending classes
    public const string FILE_TYPE = '';
    public const string FILE_PATTERN = '';

    private $fileHandle = null;
    private ?string $fullFilename = null;

    abstract public function isFileThisType(string $filename): bool;
    abstract public function getInterestData(string $fullFilename): array;

    protected function openFile(string $fullFilename): void
    {
        if (!is_null($this->fileHandle)) {
            throw new Exception('File was already open.');
        }

        $fileHandle = fopen($fullFilename, 'r');

        $this->fileHandle = $fileHandle;
        $this->fullFilename = $fullFilename;
    }

    protected function readCsvLine(): ?array
    {
        if (is_null($this->fileHandle)) {
            throw new Exception('No file is open when reading a line.');
        }

        $line = fgetcsv($this->fileHandle, 4096);
        if ($line === false) {
            return null;
        }

        return $line;
    }

    protected function closeFile()
    {
        if (is_null($this->fileHandle)) {
            throw new Exception('No file is open when trying to close.');
        }

        if (fclose($this->fileHandle) === false) {
            throw new Exception("Cannot close file '{$this->fullFilename}'.");
        }

        $this->fileHandle = null;
        $this->fullFilename = null;
    }

    protected function extractDate(string $format, string $dateString): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat($format, $dateString);
        if ($date !== false) {
            return $date;
        }

        if ($date === false) {
            throw new Exception("Invalid value found for date field: '$dateString'.");
        }

        return $date;
    }

    protected function extractAmount(string $amountString, string $thousandSeparator = ',', string $decimalSeparator = '.'): float
    {
        $amount = $amountString;

        if (strlen($thousandSeparator)) {
            $amount = str_replace($thousandSeparator, '', $amount);
        }

        if ($decimalSeparator != '.') {
            $amount = str_replace($decimalSeparator, '.', $amount);
        }

        if ((float)$amount != $amount) {
            throw new Exception("Invalid float value: $amount (original: $amountString).");
        }

        return (float)$amount;
    }
}
