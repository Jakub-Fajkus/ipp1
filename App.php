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
     * @var AppOutput
     */
    protected $output;

    /**
     * @var AppInput
     */
    protected $input;

    /**
     * @var string
     */
    protected $inputData;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var XMLParser
     */
    protected $xmlParser;

    /**
     * @var DOMDocument
     */
    protected $document;

    /**
     * App constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->output = new AppOutput();
        $this->input = new AppInput();
    }

    /**
     * @throws \InvalidQueryException
     * @throws \InputFileException
     * @throws \Exception
     * @throws \OutputFileException
     * @throws \InvalidInputFileFormatException
     */
    public function run()
    {
        if ($this->config->getPrintHelp()) {
            $this->printHelp();

            return;
        }

        $this->readInputData();

        $lexicalAnalyzer = new LexicalAnalyzer($this->config->getQuery());
        $syntacticalAnalyzer = new SyntacticalAnalyzer($lexicalAnalyzer->getTokens());

        if ($syntacticalAnalyzer->analyze() === false) {
            throw new InvalidQueryException('Syntax error in query');
        }

        $this->query = $syntacticalAnalyzer->getQuery();
        $this->query->validate();
        $this->xmlParser = new XMLParser($this->inputData, $this->query);

        if ($this->query->getFromElement() === null) {
            $this->generateEmptyOutput();

            return;
        }

        $fromElements = $this->findFromElements();

        if (count($fromElements) < 1 || $fromElements[0] === null) {
            $this->generateEmptyOutput();

            return;
        }

        $selectElements = $this->selectElements($fromElements[0]);

        $this->generateOutput($selectElements);
    }

    /**
     *
     */
    protected function printHelp()
    {
        $help = <<<HELP
    * --help                Show this help
    * -h                    Same as --help
    
    * --input=filename      Input XML file
    * -i=filename           Same as --input
    
    * --output=filename     Output XML file with the content filtered by the query
    * -o=filename           Same as --output
    
    * --query='query'       Query in the query language(the query cannot contain the apostrophe(') character)
    * -q='query'            Same as --query
    
    * --qf=filename         Query in the query language located in the external file(the query there can contain apostrophes('))
    
    * -n                    Do not generate the XML header in the output file
    
    * --root=element        Name of the pair root element containing the result elements. If the option is not present, the result elements are not contained within any element(even though that violates with the XML standard)
    * -r=element            Same as --root
HELP;

        $this->output->writeStdout($help);
    }

    /**
     * @throws InputFileException
     */
    protected function readInputData()
    {
        //if we have the output file
        if ($this->config->getInputFileName() !== '') {
            $this->inputData = $this->input->getFileContent($this->config->getInputFileName());
        } else {
            $this->inputData = $this->input->readFromStdin();
        }
    }

    /**
     * @param Query $query
     * @param XMLParser $$this->xmlParser
     *
     * @return SimpleXMLElement[]
     *
     * @throws Exception
     */
    protected function findFromElements()
    {
        $queryElement = $this->query->getFromElement();
        $findRoot = true;

        if ($queryElement->getType() === Token::TOKEN_ROOT) {
            $fromElements = [$this->xmlParser->getIterator()]; //get the root)
        } elseif ($queryElement->getType() === Token::TOKEN_ELEMENT) {
            $queryElementName = $queryElement->getValue();
            $decisionMaker = $this->getClosureForElement($queryElementName);

            $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        } elseif ($queryElement->getType() === Token::TOKEN_ATTRIBUTE) {
            $attributeName = str_replace('.', '', $queryElement->getValue()); //remove the dot at the 0 index
            $decisionMaker = $this->getClosureForAttribute($attributeName);

            $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        } elseif ($queryElement->getType() === Token::TOKEN_ELEMENT_WITH_ATTRIBUTE) {
            list($elementName, $attributeName) = explode('.', $queryElement->getValue());
            $decisionMaker = $this->getClosureForElementWithAttribute($attributeName, $elementName);
            $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        } else {
            throw new \Exception('Invalid query type');
        }

        return $fromElements;
    }

    /**
     * @param SimpleXMLElement $fromElement
     *
     * @return SimpleXMLElement[]
     * @throws \InputFileException
     *
     * @throws InvalidQueryException
     */
    protected function selectElements(SimpleXMLElement $fromElement)
    {
        return $this->xmlParser->findSelectElements($fromElement);
    }

    protected function generateEmptyOutput()
    {
        $this->generateOutput([]);
    }

    /**
     * @param SimpleXMLElement[] $elements
     * @throws \OutputFileException
     */
    protected function generateOutput($elements)
    {
        if ($this->query->getLimit() !== null) {
            $elements = array_slice($elements, 0, $this->query->getLimit()->getValue());
        }

        $xml = ElementUtils::getXmlString(
            $elements,
            $this->config->isGenerateXmlHeader(),
            $this->config->getRootElementName()
        );

        if ($this->config->getOutputFileName() === '') {
            //write to the stdout
            $this->output->writeStdout($xml);
        } elseif (false === $this->output->writeToFile($this->config->getOutputFileName(), $xml)) {
            throw new OutputFileException('Can not write to the output file');
        }
    }

    /**
     * @param $attributeName
     * @param $elementName
     * @return Closure
     */
    protected function getClosureForElementWithAttribute($attributeName, $elementName)
    {
        return function (SimpleXMLElement $rootElement, $attributes) use ($attributeName, $elementName) {
            if ($rootElement->getName() !== $elementName) {
                return false;
            }

            foreach (array_keys($attributes) as $key) {
                if ($key === $attributeName) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * @param $attributeName
     * @return Closure
     */
    protected function getClosureForAttribute($attributeName)
    {
        return function (SimpleXMLElement $rootElement, $attributes) use ($attributeName) {
            foreach ($attributes as $key => $value) {
                if ($key === $attributeName) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * @param $queryElementName
     *
     * @return Closure
     */
    protected function getClosureForElement($queryElementName)
    {
        return function (SimpleXMLElement $rootElement, $attributes) use ($queryElementName) {
            return $rootElement->getName() === $queryElementName;
        };
    }
}
