<?php

$handle = fopen($file, 'r');

new \Pre\Deferred(function() use (&$handle) {
    fclose($handle);
});

while (!feof($handle)) {
    print fgets($handle);
}
