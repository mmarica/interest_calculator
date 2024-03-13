<?php

namespace Mma\Interest;

use DirectoryIterator;

class FileSystem
{
    public static function findByExtenstion(string $folder, string $extension): array
    {
        $files = [];
        $dir = new DirectoryIterator($folder);
        
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }

            if (strtolower($fileinfo->getExtension()) != 'csv') {
                continue;
            }

            $files[] = $fileinfo->getFilename();
        }

        return $files;
    }
}
