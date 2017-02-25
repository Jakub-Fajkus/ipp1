<?php

/**
 * Class Config.
 */
class Config
{
    /**
     * @var Output
     */
    protected $output;

    /**
     * @var array Unprocessed command line parameters
     */
    protected $parameters = [];

    /**
     * @var array Array of processed parameters(in the long and the short form)
     */
    protected $processedParameters;

    /**
     * @var bool
     */
    protected $displayHelp;

    /**
     * @var bool
     */
    protected $generateXmlHeader;

    /**
     * @var string empty string or element name
     */
    protected $rootElementName;

    /**
     * @var string
     */
    protected $inputFileName;

    /**
     * @var string
     */
    protected $outputFileName;

    /**
     * @var string
     */
    protected $query;

    /**
     * Config constructor.
     *
     * @param Output $output
     * @param array  $parameters
     */
    public function __construct(Output $output, array $parameters)
    {
        $this->output = $output;
        $this->parameters = $parameters;
        $this->processedParameters = [];
        $this->generateXmlHeader = true;
        $this->rootElementName = '';
        $this->outputFileName = '';
    }

    public function processParameters()
    {
        $queryFile = null;

        if (in_array('--help', $this->parameters, true)) {
            //Tento parametr nelze kombinovat s žádným dalším parametrem, jinak skript ukončete s chybou.
            if (count($this->parameters) !== 2) {
                throw new ParametersException('Can not use --help with any other parameter');
            }

            $this->displayHelp = true;

            return;
        } else {
            $this->displayHelp = false;
        }

        /* @noinspection CallableInLoopTerminationConditionInspection */
        for ($i = 1; $i < count($this->parameters); ++$i) {
            $actual = $this->parameters[$i];

            if (strpos($actual, '--input=') === 0 || strpos($actual, '-i=') === 0) {
                //check for multiple occurrence of the same semantic argument
                if ($this->wasProcessed('--input') || $this->wasProcessed('-i')) {
                    throw new ParametersException('Cannot use input parameter twice');
                }
                $this->inputFileName = $this->getValueFromParameter($actual);
                $this->processedParameters[] = '--input';
                $this->processedParameters[] = '-i';
            } elseif (strpos($actual, '--output=') === 0 || strpos($actual, '-o=') === 0) {
                //check for multiple occurrence of the same semantic argument
                if ($this->wasProcessed('--output') || $this->wasProcessed('-o')) {
                    throw new ParametersException('Cannot use output parameter twice');
                }
                $this->outputFileName = $this->getValueFromParameter($actual);
                $this->processedParameters[] = '--output';
                $this->processedParameters[] = '-o';
            } elseif (strpos($actual, '--query=') === 0 || strpos($actual, '-q=') === 0) {
                //check for multiple occurrence of the same semantic argument
                if ($this->wasProcessed('--query') || $this->wasProcessed('-q') || $this->wasProcessed('--qf')) {
                    throw new ParametersException('Cannot use input query twice');
                }

                $this->query = $this->getValueFromParameter($actual);
                $this->processedParameters[] = '--query';
                $this->processedParameters[] = '-q';
            } elseif (strpos($actual, '--qf=') === 0) {
                if ($this->wasProcessed('--qf') || $this->wasProcessed('--query') || $this->wasProcessed('-q')) {
                    throw new ParametersException('Cannot use input parameter twice');
                }

                $this->processedParameters[] = '--qf';
                $queryFile = $actual;
            } elseif (strpos($actual, '-n') === 0) {
                if ($this->wasProcessed('--n')) {
                    throw new ParametersException('Cannot use n parameter twice');
                }

                $this->generateXmlHeader = false;
            } elseif (strpos($actual, '--root=') === 0 || strpos($actual, '-r=') === 0) {
                //check for multiple occurrence of the same semantic argument
                if ($this->wasProcessed('--root') || $this->wasProcessed('-r')) {
                    throw new ParametersException('Cannot use root parameter twice');
                }
                $this->processedParameters[] = '--root';
                $this->processedParameters[] = '-r';

                $this->rootElementName = $this->getValueFromParameter($actual);
            } else {
                throw new ParametersException('Unknown parameter: '.$actual);
            }
        }

        //delayed query file processing
        if (in_array('--qf', $this->processedParameters, true)) {
            $this->getQueryFromFile($this->getValueFromParameter($queryFile));
        }

        if (!in_array('--input', $this->processedParameters, true)) {
            //todo: read from stdin
        }

        if (!in_array('--output', $this->processedParameters, true)) {
            //todo: write to stdout
        }

        echo "FILE:".$this->outputFileName . PHP_EOL;

        if (!is_file($this->inputFileName)) {
            throw new InputFileException('Can not open input file');
        }
    }

    /**
     * @return bool
     */
    public function getPrintHelp()
    {
        return $this->displayHelp;
    }

    /**
     * @param bool $displayHelp
     */
    public function setDisplayHelp($displayHelp)
    {
        $this->displayHelp = $displayHelp;
    }

    /**
     * @return string
     */
    public function getInputFileName()
    {
        return $this->inputFileName;
    }

    /**
     * @param string $inputFileName
     */
    public function setInputFileName($inputFileName)
    {
        $this->inputFileName = $inputFileName;
    }

    /**
     * @return string
     */
    public function getOutputFileName()
    {
        return $this->outputFileName;
    }

    /**
     * @param string $outputFileName
     */
    public function setOutputFileName($outputFileName)
    {
        $this->outputFileName = $outputFileName;
    }

    /**
     * @return Output
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param Output $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getProcessedParameters()
    {
        return $this->processedParameters;
    }

    /**
     * @param array $processedParameters
     */
    public function setProcessedParameters($processedParameters)
    {
        $this->processedParameters = $processedParameters;
    }

    /**
     * @return bool
     */
    public function isGenerateXmlHeader()
    {
        return $this->generateXmlHeader;
    }

    /**
     * @param bool $generateXmlHeader
     */
    public function setGenerateXmlHeader($generateXmlHeader)
    {
        $this->generateXmlHeader = $generateXmlHeader;
    }

    /**
     * @return string
     */
    public function getRootElementName()
    {
        return $this->rootElementName;
    }

    /**
     * @param string $rootElementName
     */
    public function setRootElementName($rootElementName)
    {
        $this->rootElementName = $rootElementName;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @param $parameter
     *
     * @return bool
     */
    protected function wasProcessed($parameter)
    {
        return in_array($parameter, $this->processedParameters, true);
    }

    /**
     * @param string $parameter
     *
     * @return string Value after the =
     */
    protected function getValueFromParameter($parameter)
    {
        return  substr($parameter, strpos($parameter, '=') + 1); //+1 character after the =
    }

    /**
     * @param $fileName
     */
    protected function getQueryFromFile($fileName)
    {
        if (is_file($fileName)) {
            $this->query = file_get_contents($fileName);
        } else {
            if (is_file(__DIR__.'/'.$fileName)) {
                $this->query = file_get_contents(__DIR__.'/'.$fileName);
            } else {
                //                throw new InputFileException('N') //todo
            }
        }

        if ($this->query === false) {
            throw new InvalidQueryException('Could not read a query from the file: '.$fileName);
        }
    }
}
