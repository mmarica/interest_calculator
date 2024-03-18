<?php

namespace Mma\Interest\TransactionFile;

use DateTimeImmutable;
use Exception;

class RaiffeisenXlsx extends AbstractFileReader
{
    public const string FILE_TYPE = 'raiffeisen_xlsx';
    public const string FILE_PATTERN = 'Raiffeisen_*.XLSX';

    private const string WORKSHEET = 'Pagina 1';

    public function isFileThisType(string $filename): bool
    {
        return str_starts_with($filename, 'Raiffeisen_') && str_ends_with($filename, '.XLSX');
    }

    public function getInterestData(string $fullFilename): array
    {
        $interestData = [];

        $reader = (\PhpOffice\PhpSpreadsheet\IOFactory::createReader(\PhpOffice\PhpSpreadsheet\IOFactory::READER_XLSX))
            ->setReadDataOnly(true)
            ->setLoadSheetsOnly(self::WORKSHEET);

        $xls = $reader->load($fullFilename)->getActiveSheet();
        foreach ($xls->toArray() as $line) {
            if ($line[11] == 'PLATA AUTOMATA DOB.') {
                $date = $this->extractDateTime($line[1]);
                $interest = (float)$line[3];
                $interestData[] = [
                    'date' => $date,
                    'interest' => $interest,
                ];
            }

            if ($line[11] == 'IMPOZIT RETINUT') {
                $date = $this->extractDateTime($line[1]);
                $interest = -(float)$line[2];
                $interestData[] = [
                    'date' => $date,
                    'interest' => $interest,
                ];
            }
        }

        return $interestData;
    }

    private function extractDateTime(string $dateString): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('m/d/Y', $dateString);
        if ($date !== false) {
            return $date;
        }

        if ($date === false) {
            throw new Exception("Invalid value found for date field: '$dateString'.");
        }

        return $date;
    }
}
