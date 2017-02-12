<?php

/**
 * Class OutputFileException.
 */
class OutputFileException extends \Exception
{
    const CODE = 3;
    const MESSAGE = 'The output file could not be opened for writing(prmissions, the file already exists).';

    /**
     * Get the general message with the specific message
     *
     * @return string
     */
    public function getCustomMessage()
    {
        return self::MESSAGE . 'With specific reason: ' . $this->message;
    }
}
