<?php

include_once __DIR__.'/classes.php';

// get parameters and consctruct the Config object
$output = new Output();
$config = new Config($output, $argv);
$app = new App($config, $output);

//var_dump($argv);

try {
    $config->processParameters();
    $app->run();
} catch (InputFileException $exception) {
    $output->writeStderr($exception->getCustomMessage());
    exit($exception::CODE);
} catch (InvalidQueryException $exception) {
    $output->writeStderr($exception->getCustomMessage());
    exit($exception::CODE);
} catch (OutputFileException $exception) {
    $output->writeStderr($exception->getCustomMessage());
    exit($exception::CODE);
} catch (ParametersException $exception) {
    $output->writeStderr($exception->getCustomMessage());
    exit($exception::CODE);
} catch (\Exception $exception) {
    $output->writeStdout('Other error');
    var_dump($exception); //todo: remove!
    exit(4); //the SimpleXMLElement throws an exception while parsing
}

exit(0);
