<?php

    $rootFolder = dirname(__FILE__, 2);
    require $rootFolder . '/vendor/autoload.php';

    use Mma\Interest\InterestReport;

    $inputFolder = $rootFolder . '/input';
    $interestReport = new InterestReport();
    $interestReport->generate($inputFolder);
