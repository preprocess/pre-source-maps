<?php

namespace Pre\SourceMaps;

define('COMMENT_TEXT', 'PRE_LINE');

function map(string $inputPath, string $outputPath, \Closure $parser) {
    $inputLines = 🔒addLineNumberComments(file($inputPath));
    $outputLines = 🔒parse($inputLines, $parser);

    file_put_contents(
        $outputPath,
        🔒combineWithTrailingNewline(
            🔒removeLineNumberComments($outputLines)
        )
    );

    $map = 🔒createMap(
        🔒getInputAndOutputLineNumbers($outputLines)
    );

    file_put_contents(
        "{$outputPath}.map",
        json_encode($map)
    );
}

function 🔒addLineNumberComments(array $lines): array
{
    return array_map(function($line, $lineNumber) {
        $trimmedLine = rtrim($line);

        if (empty($trimmedLine)) {
            return '';
        }

        return "{$trimmedLine} // ".COMMENT_TEXT." {$lineNumber}";
    }, $lines, array_keys($lines));
}

function 🔒parse(array $lines, \Closure $parser): array
{
    return explode(PHP_EOL, $parser(join(PHP_EOL, $lines)));
}

function 🔒getInputAndOutputLineNumbers(array $lines): array
{
    $keyedLines = array_map(function($line, $lineNumber) {
        return [$line, $lineNumber];
    }, $lines, array_keys($lines));

    $groups = array_map(function($data) {
        [$line, $lineNumber] = $data;

        preg_match('/'.COMMENT_TEXT.' (\d+)$/', $line, $matches);

        if ($matches and count($matches) == 2) {
            return [$matches[1], $lineNumber];
        }
        
        return null;
    }, $keyedLines);

    return array_values(array_filter($groups));
}

function 🔒createMap(array $lines): array
{
    return array_reduce($lines, function($carry, $data) {
        [$inputLineNumber, $outputLineNumber] = $data;

        $carry[$outputLineNumber] = (int) $inputLineNumber;

        return $carry;
    }, []);
}

function 🔒removeLineNumberComments(array $lines): array
{
    return array_map(function($line) {
        return rtrim(preg_replace('/\/\/ '.COMMENT_TEXT.' \d+$/', '', $line));
    }, $lines);
}

function 🔒combineWithTrailingNewline(array $lines): string
{
    return rtrim(join(PHP_EOL, $lines)) . PHP_EOL;
}

function locate(string $inputPath, \Throwable $throwable): \Throwable
{
    $actualLineNumber = 🔒getActualErrorLine($throwable);

    if (!$actualLineNumber) {
        return $throwable;
    }

    return 🔒newActualThrowable($inputPath, $actualLineNumber, $throwable);
}

function 🔒getActualErrorLine(\Throwable $throwable)
{
    $file = $throwable->getFile();
    $line = $throwable->getLine();

    $map = json_decode(file_get_contents("{$file}.map"), true);

    $found = null;

    foreach (array_keys($map) as $outputLine) {
        if ($outputLine >= $line - 1) {
            $found = $map[$outputLine];
            break;
        }
    }

    return $found;
}

function 🔒newActualThrowable(string $inputPath, int $actualLineNumber, \Throwable $throwable)
{
    $reflection = new \ReflectionObject($throwable);

    $fileProperty = $reflection->getProperty('file');
    $fileProperty->setAccessible(true);
    $fileProperty->setValue($throwable, $inputPath);

    $lineProperty = $reflection->getProperty('line');
    $lineProperty->setAccessible(true);
    $lineProperty->setValue($throwable, $actualLineNumber);

    return $throwable;
}
