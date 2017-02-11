<?php

/**
 * Class Output.
 */
class Output
{
    /**
     * @var resource
     */
    protected $stdOut;

    /**
     * @var resource
     */
    protected $stdErr;

    /**
     * Output constructor.
     */
    public function __construct()
    {
        $this->stdOut = fopen('php://stdout', 'w');
        $this->stdErr = fopen('php://stderr', 'w');
    }

    /**
     * @param $text
     * @param bool $addEol
     */
    public function writeStdout($text, $addEol = true)
    {
        $this->write($this->stdOut, $text.($addEol ? PHP_EOL : ''));
    }

    /**
     * @param $text
     * @param bool $addEol
     */
    public function writeStderr($text, $addEol = true)
    {
        $this->write($this->stdErr, $text.($addEol ? PHP_EOL : ''));
    }

    /**
     * @param resource $stream
     * @param string   $text
     */
    protected function write($stream, $text)
    {
        fwrite($stream, $text);
    }
}
