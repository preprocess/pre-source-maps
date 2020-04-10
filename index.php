<?php

require_once __DIR__ . '/vendor/autoload.php';

$inputPath = __DIR__ . '/input.pre';
$outputPath = __DIR__ . '/output.php';

$parser = function($input) {
    return <<<'CODE'
    <?php                                           // PRE_LINE 0

    $handle = fopen($file, 'r');                    // PRE_LINE 2

    new \Pre\Deferred(function() use (&$handle) {
        fclose($handle);
    });                                             // PRE_LINE 4
    
    while (!feof($handle)) {                        // PRE_LINE 6
        print fgets($handle);                       // PRE_LINE 7
    }                                               // PRE_LINE 8
    CODE;
};

\Pre\Sourcemaps\map($inputPath, $outputPath, $parser);
