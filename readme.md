# Source Maps

This library provides a couple functions for creating and using source maps in preprocessed code.

## Create source maps

```php
\Pre\SourceMaps\map(
    '/path/to/input-file.php',
    '/path/to/output-file.php',
    $parser
);
```

This function takes an input file, with all your new syntax, and runs it through your parser. It write an output file to the filesystem, and a map file to go with it. The map file looks like this:

```json
{"0":0,"2":2,"3":3,"9":5}
```

The keys are line numbers from the output file (once it has been parsed by your parser). The values are the line numbers of the corresponing lines in the source file.

## Locate true error lines

```php
$newThrowable = \Pre\SourceMaps\locate(
    '/path/to/input-file.php',
    $throwable
);
```

This function changes the `file` and `line` properties of a throwable, so that it reflects where the error happened in the source file. Imagine you had the following superset code:

```php
$handle = fopen($file, 'r');

defer fclose($handle);

while (!feof($handle)) {
    print fgets($handle);
}
```

...and, imagine your custom parser produced the following output code:

```php
$handle = fopen($file, 'r');

new \Pre\Deferred(function() use (&$handle) {
    fclose($handle);
});

while (!feof($handle)) {
    print fgets($handle);
}
```

If `fclose` were to throw an exception, you'd probably want to be pointed to line 3 of your source file. The exception is thrown with your output file path and line 4. The `locate` function will change that exception to reflect your input file and line 3.

> Note that line numbers are zero-indexed, so adjust your error display accordingly.
