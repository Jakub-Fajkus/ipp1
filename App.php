<?php

/**
 * Class App.
 */
class App
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Output
     */
    protected $output;

    /**
     * @var string
     */
    protected $inputData;

    /**
     * App constructor.
     *
     * @param Config $config
     * @param Output $output
     */
    public function __construct(Config $config, Output $output)
    {
        $this->config = $config;
        $this->output = $output;
    }

    /**
     *
     */
    public function run()
    {
        if ($this->config->getPrintHelp()) {
            $this->printHelp();

            return;
        }

        $this->readInputData();
        $lexicalAnalyzer = new LexicalAnalyzer($this->config->getQuery());
        $tokens = $lexicalAnalyzer->getTokens();


    }

    /**
     *
     */
    protected function printHelp()
    {
        $this->output->writeStdout('HELP'); //todo:!
    }

    /**
     * @throws InputFileException
     */
    protected function readInputData()
    {
        $this->inputData = file_get_contents($this->config->getInputFileName());

        if ($this->inputData === false) {
            throw new InputFileException();
        }
    }
}
