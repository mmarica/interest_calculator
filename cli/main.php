<?php

    $rootFolder = dirname(__FILE__, 2);
    require $rootFolder . '/vendor/autoload.php';

    use Mma\Interest\FileSystem;

    $inputFolder = $rootFolder . '/input';
    print_r(FileSystem::findByExtenstion($inputFolder, 'csv'));
