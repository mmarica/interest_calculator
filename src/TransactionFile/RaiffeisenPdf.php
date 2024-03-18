<?php

namespace Mma\Interest\TransactionFile;

use Exception;

class RaiffeisenPdf extends AbstractFileReader
{
    public const string FILE_TYPE = 'raiffeisen_pdf';
    public const string FILE_PATTERN = 'Raiffeisen_*.PDF';

    private const string COL_DOBANDA = 'plata dobanda';
    private const string COL_IMPOZIT = 'impozit pe dobanda';

    public function isFileThisType(string $filename): bool
    {
        return str_starts_with($filename, 'Raiffeisen_') && str_ends_with($filename, '.PDF');
    }

    public function getInterestData(string $fullFilename): array
    {
        $interestData = [];

        $tmpFile = tempnam(sys_get_temp_dir(), 'pdftotext_');
        if ($tmpFile === false) {
            throw new Exception("Cannot create temporary file.");
        }

        try {
            $cmd = sprintf("pdftotext -layout %s %s 2>&1", escapeshellarg($fullFilename), escapeshellarg($tmpFile));
            exec($cmd, $output, $exitCode);

            if ($exitCode != 0) {
                unlink($tmpFile);
                throw new Exception("Error encountered when converting PDF file:\n" . implode("\n", $output));
            }

            $content = @file_get_contents($tmpFile);
            if ($content === false) {
                throw new Exception("Cannot read content of file: $tmpFile.");
            }

            $content = @file_get_contents($tmpFile);
            preg_match_all("/([\d]+\.[\d]+\.[\d]+)\s+([\d]+\.[\d]+\.[\d]+)\s+(\w+[\s\w]+)\s+(\d+[\,]*\d*[\.]*\d+)/", $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $line) {
                $action = trim($line[3]);

                if (in_array($action, [self::COL_DOBANDA, self::COL_IMPOZIT])) {
                    $date = $this->extractDate('d.m.Y', $line[2]);
                    $interest = $this->extractAmount($line[4]);

                    if ($action == self::COL_IMPOZIT) {
                        $interest = -$interest;
                    }

                    $interestData[] = [
                        'date' => $date,
                        'interest' => $interest,
                    ];
                }
            }
        } finally {
            unlink($tmpFile);
        }

        return $interestData;
    }
}
