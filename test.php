<?php

$dir = './tests/';

$files = [];

// Open a directory, and read its contents
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if (strpos($file, '.in')) {
                $files[] = substr($file, 0, strpos($file, '.'));
            }
        }
        closedir($dh);
    }
}

$countOfTests = 0;
$failed = 0;

foreach ($files as $file) {
    $testFailed = false;
    ++$countOfTests;

    echo "TEST: $file".PHP_EOL;

    $actualCode = null;
    $actualOutput = [];
    exec(file_get_contents($dir.$file.'.in'), $actualOutput, $actualCode);
    $actualOutput = implode('', $actualOutput);
    $actualOutput = trim($actualOutput);
    $actualCode = trim($actualCode);

    $expectedOutput = trim(file_get_contents($dir.$file.'.out'));
    $expectedCode = trim(file_get_contents($dir.$file.'.code'));

    if ($actualOutput !== $expectedOutput) {
        $testFailed = true;
        echo 'FAILED'.PHP_EOL;

        echo 'EXPECTED:>>>>'.PHP_EOL;
        echo implode('', $actualOutput);
        echo PHP_EOL.'<<<<<'.PHP_EOL;

        echo 'ACTUAL:>>>>'.PHP_EOL;
        echo $expectedOutput;
        echo PHP_EOL.'<<<<<'.PHP_EOL;

        var_dump($actualOutput, $expectedOutput);
    } else {
        echo 'OUTPUT OK'.PHP_EOL;
    }

    if ($expectedCode !== $actualCode) {
        $testFailed = true;
        echo 'FAILED'.PHP_EOL;

        echo 'EXPECTED CODE:>>>>'.PHP_EOL;
        echo $expectedCode;
        echo PHP_EOL.'<<<<<'.PHP_EOL;

        echo 'ACTUAL CODE:>>>>'.PHP_EOL;
        echo $actualCode;
        echo PHP_EOL.'<<<<<'.PHP_EOL;
    } else {
        echo 'CODE OK'.PHP_EOL;
    }

    if ($testFailed) {
        ++$failed;
    }

    echo PHP_EOL.PHP_EOL;
}

echo "Failed tests: $failed/$countOfTests".PHP_EOL;
