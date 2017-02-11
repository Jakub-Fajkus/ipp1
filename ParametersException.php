<?php

/**
 * Class ParametersException
 */
class ParametersException extends \Exception
{
    const CODE = 1;

    const MESSAGE = 'Invalid combination of the parameters or unknown parameter';
}