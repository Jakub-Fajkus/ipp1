<?php

include_once __DIR__.'/classes.php';

// get parameters and consctruct the Config object
$config = new Config($argv);
$app = new App($config);
$output = new AppOutput();


try {
    $config->processParameters();
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
    exit(4); //the SimpleXMLElement throws an exception while parsing
}

exit(0);
