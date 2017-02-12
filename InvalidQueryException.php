<?php

/**
 * Class InvalidQueryException.
 */
class InvalidQueryException extends \Exception
{
    const CODE = 80;
    const MESSAGE = 'Syntactic or semantic error in the query';

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
