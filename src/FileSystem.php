<?php

namespace Mma\Interest;

use DirectoryIterator;

class FileSystem
{
    public static function findByPatternList(string $folder, array $patternList): array
    {
        $files = [];
        $dir = new DirectoryIterator($folder);
        
        foreach ($dir as $fileinfo) {
            $file = $fileinfo->getFilename();

            $patternMatched = false;
            foreach ($patternList as $pattern) {
                if (self::fileMatchesPattern($file, $pattern)) {
                    $patternMatched = true;
                    break;
                }
            }

            if (!$patternMatched) {
                continue;
            }

            if (!is_file($folder . '/' . $file)) {
                continue;
            }

            $files[] = $file;
        }

        sort($files);
        return $files;
    }

    public static function fileMatchesPattern(string $filename, string $pattern, bool $ignoreCase = false): bool
    {
        $expr = preg_replace_callback('/[\\\\^$.[\\]|()?*+{}\\-\\/]/', function($matches) {
            switch ($matches[0]) {
            case '*':
                return '.*';
            case '?':
                return '.';
            default:
                return '\\'.$matches[0];
            }
        }, $pattern);

        $expr = '/'.$expr.'/';
        if ($ignoreCase) {
            $expr .= 'i';
        }

        return (bool) preg_match($expr, $filename);
    }
}
