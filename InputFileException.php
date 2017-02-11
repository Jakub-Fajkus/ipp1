<?php

/**
 * Class InputFileException
 */
class InputFileException extends \Exception
{
    const CODE = 2;
    const MESSAGE = 'Input file does not exist or it could not be opened.';
}