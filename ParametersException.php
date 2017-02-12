<?php

/**
 * Class ParametersException.
 */
class ParametersException extends \Exception
{
    const CODE = 1;

    const MESSAGE = 'Invalid combination of the parameters or unknown parameter';

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
