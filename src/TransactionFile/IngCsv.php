<?php

namespace Mma\Interest\TransactionFile;

use DateTimeImmutable;
use Exception;

class IngCsv extends AbstractFileReader
{
    public const string FILE_TYPE = 'ing_csv';

    public function isFileThisType(string $filename): bool
    {
        return str_starts_with(strtolower($filename), 'ing_');
    }

    public function getInterestData(string $fullFilename): array
    {
        $interestData = [];
        $this->openFile($fullFilename);

        $lineNumber = 0;
        do {
            $line = $this->readLine();
            $lineNumber++;
            
            // end of file
            if (is_null($line)) {
                break;
            }

            if (in_array('Actualizare dobanda', $line)) {
                if (count($line) != 8 || $line[3] != 'Actualizare dobanda') {
                    throw new Exception("Invalid format encountered for line #$lineNumber in $fullFilename: " . print_r($line, true) . ".");
                }

                $interestData[] = $this->decodeInterestLine($line);
            }
        } while(true);

        $this->closeFile();

        return $interestData;
    }

    private function decodeInterestLine(array $line): array
    {
        $date = $this->extractDateTime($line[0]);

        $interestString = $line[6];
        // hacky conversion from romanian to english number decimals format
        // should find a cleaner way to do it at some point
        $interest = (float)str_replace(',', '.', $interestString);

        return [
            'date' => $date,
            'interest' => $interest,
        ];
    }

    private function extractDateTime(string $dateString): DateTimeImmutable
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

        $date = DateTimeImmutable::createFromFormat('d F Y', $dateString);
        if ($date !== false) {
            return $date;
        }

        // there can be an alternate format in the file, try to match that one too
        $date = DateTimeImmutable::createFromFormat('d-M-Y', $dateString);

        if ($date === false) {
            throw new Exception("Invalid value found for date field: '$dateString'.");
        }

        return $date;
    }
}
