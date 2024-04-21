<?php

namespace Mma\Interest\TransactionFile;

use DateTimeImmutable;
use Exception;

class IngCsv extends AbstractFileReader
{
    public const string FILE_TYPE = 'ing_csv';
    public const string FILE_PATTERN = 'ING_*.csv';

    public function isFileThisType(string $filename): bool
    {
        return str_starts_with($filename, 'ING_') && str_ends_with($filename, '.csv');
        return str_starts_with(strtolower($filename), 'ING_');
    }

    public function getInterestData(string $fullFilename): array
    {
        $interestData = [];
        $this->openFile($fullFilename);

        $lineNumber = 0;
        do {
            $line = $this->readCsvLine();
            $lineNumber++;
            
            // end of file
            if (is_null($line)) {
                break;
            }

            // skip incoming payments
            if (count($line) >= 4 && $line[3] == 'Incasare') {
                continue;
            }

            if (in_array('Actualizare dobanda', $line)) {
                if (count($line) != 8 || $line[3] != 'Actualizare dobanda') {
                    throw new Exception("[1] Invalid format encountered for line #$lineNumber in $fullFilename: " . print_r($line, true) . ".");
                }

                $interestData[] = [
                    'date' => $this->decodeDate($line[0]),
                    'interest' => $this->extractAmount($line[6], '.', ','),
                ];

                continue;
            }

            if (in_array('Dobanda aferenta depozitului', $line)) {
                if (count($line) != 8 || $line[3] != 'Dobanda aferenta depozitului') {
                    throw new Exception("[2] Invalid format encountered for line #$lineNumber in $fullFilename: " . print_r($line, true) . ".");
                }

                $interestData[] = [
                    'date' => $this->decodeDate($line[0]),
                    'interest' => $this->extractAmount($line[6], '.', ','),
                ];

                continue;
            }

            // safeguard for unknown operations
            if (count($line) >= 4 && strlen($line[3])) {
                // ignore lines with known values in "operation" column
                $excludeStrings = [
                    'Detalii tranzactie',
                    "Transfer Home'Bank",
                    'Taxe si comisioane',
                    'Lichidare depozit',
                    'Cumparare POS',
                    'Plata debit direct',
                    'Retragere numerar',
                    'Incasare via card',
                    'Cumparare POS - stornare',
                ];
                if (in_array($line[3], $excludeStrings)) {
                    continue;
                }

                // ignore lines that start with known values in "operation" column
                $excludeStringStarts = [
                    'Referinta: ',
                    'Beneficiar: ',
                    'In contul: ',
                    'Principal: ',
                    'Taxa lunara serviciu Alerte: ',
                    'Impozit pe dobanda: ',
                    'Banca: ',
                    'Detalii: ',
                    'Ordonator: ',
                    'Din contul: ',
                    'Nr. card: ',
                    'Terminal: ',
                    'Autorizare: ',
                    'Data: ',
                    'Suma: ',
                    'Suma transmisa spre decontare: ',
                    "Home'Bank taxa lunara de utilizare cu digipass: ",
                    " REFUND ",
                    "Cod Fiscal Platitor: ",
                    "Comision emitere card livrat prin curier: ",
                ];
                foreach ($excludeStringStarts as $excludeStringStart) {
                    if (str_starts_with($line[3], $excludeStringStart)) {
                        continue 2;
                    }
                }

                // phone number
                if (is_numeric($line[3]) && round($line[3] * 1) == $line[3]) {
                    continue;
                }

                throw new Exception("[3] Invalid format encountered for line #$lineNumber in $fullFilename: " . print_r($line, true) . ".");
            }
        } while(true);

        $this->closeFile();
        return $interestData;
    }

    private function decodeDate(string $dateString): DateTimeImmutable
    {
        // ugly hack to "translate" months in date
        // should fix using setlocale(LC_TIME,"ro_RO.UTF-8") - could not get it to work yet
        $months = [
            'ianuarie' => 'January',
            'februarie' => 'February',
            'martie' => 'March',
            'aprilie' => 'April',
            'mai' => 'May',
            'iunie' => 'June',
            'iulie' => 'July',
            'septembrie' => 'September',
            'octombrie' => 'October',
            'noiembrie' => 'November',
            'decembrie' => 'December',
        ];
        foreach ($months as $roMonth => $enMonth) {
            $dateString = str_replace($roMonth, $enMonth, $dateString);
        }

        try {
            $date = $this->extractDate('d F Y', $dateString);
        } catch (Exception) {
            // there can be an alternate format in the file, try to match that one too
            $date = $this->extractDate('d-M-Y', $dateString);
        }

        return $date;
    }
}
