<?php

namespace Mma\Interest\TransactionFile;

use DateTimeImmutable;
use Exception;

class RaiffeisenXlsx extends AbstractFileReader
{
    public const string FILE_TYPE = 'raiffeisen_xlsx';
    public const string FILE_PATTERN = 'Raiffeisen_*.XLSX';

    private const string WORKSHEET = 'Pagina 1';
    private const string COL_DOBANDA = 'PLATA AUTOMATA DOB.';
    private const string COL_IMPOZIT = 'IMPOZIT RETINUT';

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
            $action = $line[11];
            if (in_array($action, [self::COL_DOBANDA, self::COL_IMPOZIT])) {
                $date = $this->extractDate('d/m/Y', $line[1]);

                if ($action == self::COL_IMPOZIT) {
                    $interest = -$this->extractAmount($line[2]);
                } else {
                    $interest = $this->extractAmount($line[3]);
                }

                $interestData[] = [
                    'date' => $date,
                    'interest' => $interest,
                ];
            }
        }

        return $interestData;
    }
}
