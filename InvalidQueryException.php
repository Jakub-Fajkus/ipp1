<?php

/**
 * Class InvalidQueryException
 */
class InvalidQueryException extends \Exception
{
    const CODE = 80;
    const MESSAGE = 'Syntactic or semantic error in the query';
}