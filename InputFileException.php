<?php

/**
 * Class InputFileException.
 */
class InputFileException extends \Exception
{
    const CODE = 2;
    const MESSAGE = 'Input file does not exist or it could not be opened.';

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
