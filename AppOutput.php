<?php

/**
 * Class Output.
 *
 * Covers the output to the operating system - console and file.
 */
class AppOutput
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
     * Write to the stdout
     *
     * @param $text
     * @param bool $addEol
     */
    public function writeStdout($text, $addEol = true)
    {
        $this->writeToFile($this->stdOut, $text.($addEol ? PHP_EOL : ''));
    }

    /**
     * Write to the stdout
     *
     * @param $text
     * @param bool $addEol
     */
    public function writeStderr($text, $addEol = true)
    {
        $this->writeToFile($this->stdErr, $text.($addEol ? PHP_EOL : ''));
    }

    /**
     * @param $fileName
     * @param $xml
     *
     * @return int|bool
     */
    public function writeToFile($fileName, $xml)
    {
        return file_put_contents($fileName, $xml);
    }
}
