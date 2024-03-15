<?php

namespace Mma\Interest;

use DirectoryIterator;

class FileSystem
{
    public static function findByExtension(string $folder, string $extension): array
    {
        $files = [];
        $dir = new DirectoryIterator($folder);
        
        foreach ($dir as $fileinfo) {
            if (strtolower($fileinfo->getExtension()) != 'csv') {
                continue;
            }

            $file = $fileinfo->getFilename();
            if (!is_file($folder . '/' . $file)) {
                continue;
            }

            $files[] = $file;
        }

        sort($files);
        return $files;
    }
}
