<?php

include_once __DIR__.'/classes.php';

// get parameters and consctruct the Config object
$output = new Output();
$config = new Config($output, $argv);
$app = new App($config, $output);

//var_dump($argv);

try {
    $config->processParameters();
    //todo: eventually read from the stdin!
    $app->run();
} catch (InputFileException $exception) {
    $output->writeStderr($exception->getCustomMessage());
    $output->writeStderr($exception->getTraceAsString());
    exit($exception::CODE);
} catch (InvalidQueryException $exception) {
    $output->writeStderr($exception->getCustomMessage());
    $output->writeStderr($exception->getTraceAsString());
    exit($exception::CODE);
} catch (OutputFileException $exception) {
    $output->writeStderr($exception->getCustomMessage());
    $output->writeStderr($exception->getTraceAsString());
    exit($exception::CODE);
} catch (ParametersException $exception) {
    $output->writeStderr($exception->getCustomMessage());
    $output->writeStderr($exception->getTraceAsString());
    exit($exception::CODE);
} catch (InvalidInputFileFormatException $exception) {
    $output->writeStderr($exception->getCustomMessage());
    $output->writeStderr($exception->getTraceAsString());
    exit($exception::CODE);
} catch (\Exception $exception) {
    $output->writeStdout('Other error');
    $output->writeStderr($exception->getTraceAsString());
    var_dump($exception); //todo: remove!
    exit(4); //the SimpleXMLElement throws an exception while parsing
}

exit(0);
