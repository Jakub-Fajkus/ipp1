<?php

/**
 * Class OutputFileException.
 */
class OutputFileException extends \Exception
{
    const CODE = 3;
    const MESSAGE = 'The output file could not be opened for writing(prmissions, the file already exists).';
}
