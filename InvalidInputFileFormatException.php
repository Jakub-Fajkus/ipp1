<?php

/**
 * Class InvalidInputFileFormatException
 */
class InvalidInputFileFormatException extends \Exception
{
    const CODE = 4;
    const MESSAGE = 'The input file has invalid format';

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