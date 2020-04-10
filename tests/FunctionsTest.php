<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testCanAddLineNumberComments()
    {
        $input = explode(PHP_EOL,
            <<<'CODE'
                <?php

                defer fclose($handle);
            CODE
        );

        $output = \Pre\SourceMaps\🔒addLineNumberComments($input);

        $expected = explode(PHP_EOL,
            <<<'CODE'
                <?php // PRE_LINE 0

                defer fclose($handle); // PRE_LINE 2
            CODE
        );

        $this->assertEquals($expected, $output);
    }

    public function testCanParse()
    {
        $input = ['hello', 'world'];

        $parser = function(string $code): string {
            return strtoupper($code);
        };

        $output = \Pre\SourceMaps\🔒parse($input, $parser);

        $expected = ['HELLO', 'WORLD'];

        $this->assertEquals($expected, $output);
    }

    public function testCanGetInputAndOutputLineNumbers()
    {
        $input = explode(PHP_EOL,
            <<<'CODE'
                <?php // PRE_LINE 0

                new \Pre\Deferred(function() use (&$handle) {
                    fclose($handle);
                }); // PRE_LINE 2
            CODE
        );

        $output = \Pre\SourceMaps\🔒getInputAndOutputLineNumbers($input);

        $expected = [
            [0, 0],
            [2, 4],
        ];

        $this->assertEquals($expected, $output);
    }

    public function testCanCreateMap()
    {
        $input = [
            [0, 0],
            [2, 4],
        ];

        $output = \Pre\SourceMaps\🔒createMap($input);

        // 🔒getInputAndOutputLineNumbers returns arrays of [$inputLine, $outputLine]
        // 🔒map flips these numbers around so we can look up a stack trace error
        // line number and get the input line number
        $expected = json_decode('{"0":0,"4":2}', true);

        $this->assertEquals($expected, $output);
    }

    public function testCanRemoveLineNumberComments()
    {
        $input = explode(PHP_EOL,
            <<<'CODE'
                <?php // PRE_LINE 0

                new \Pre\Deferred(function() use (&$handle) {
                    fclose($handle);
                }); // PRE_LINE 2
            CODE
        );

        $output = \Pre\SourceMaps\🔒removeLineNumberComments($input);

        $expected = explode(PHP_EOL,
            <<<'CODE'
                <?php

                new \Pre\Deferred(function() use (&$handle) {
                    fclose($handle);
                });
            CODE
        );

        $this->assertEquals($expected, $output);
    }

    public function testCanCombineWithTrailingNewline()
    {
        $input = ['hello', 'world'];

        $output = \Pre\SourceMaps\🔒combineWithTrailingNewline($input);

        $expected = join(PHP_EOL, ['hello', 'world', '']);

        $this->assertEquals($expected, $output);
    }

    public function testCanMap()
    {
        $inputFixturePath = __DIR__ . '/fixtures/input.pre';
        $inputPath = __DIR__ . '/fixtures/input-copy.pre';
    
        // first, make sure the file doesn't already exist
        exec("rm {$inputPath} > /dev/null");

        // copy the fixture file
        exec("cp {$inputFixturePath} {$inputPath} > /dev/null");

        $outputFixturePath = __DIR__ . '/fixtures/output-copy.php';
        $outputPath = __DIR__ . '/fixtures/output-copy.php';

        $parser = function() {
            return <<<'CODE'
            <?php // PRE_LINE 0

            $handle = fopen($file, 'r'); // PRE_LINE 2

            new \Pre\Deferred(function() use (&$handle) {
                fclose($handle);
            }); // PRE_LINE 4

            while (!feof($handle)) { // PRE_LINE 6
                print fgets($handle); // PRE_LINE 7
            } // PRE_LINE 8
            CODE;
        };

        \Pre\SourceMaps\map($inputPath, $outputPath, $parser);

        $this->assertEquals(md5_file($outputPath), md5_file($outputFixturePath));
        $this->assertEquals(md5_file("{$outputPath}.map"), md5_file("{$outputFixturePath}.map"));
    }
}
