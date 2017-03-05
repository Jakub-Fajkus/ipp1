<?php

/**
 * Class App.
 *
 * Covers the basic application logic.
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
     * Entrypoint for the whole application.
     *
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
        $this->createQuery();

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

        $selectElements = $this->getSelectedElements($fromElements[0]);

        $this->generateOutput($selectElements);
    }

    /**
     * Print help of the application to the console.
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
     * Get all elements that matches the element from the FROM clause
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
            $fromElements = [$this->xmlParser->getRoot()]; //get the root
        } elseif ($queryElement->getType() === Token::TOKEN_ELEMENT) {
            $queryElementName = $queryElement->getValue();
            $decisionMaker = $this->query->getClosureForElement($queryElementName);

            $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        } elseif ($queryElement->getType() === Token::TOKEN_ATTRIBUTE) {
            $attributeName = str_replace('.', '', $queryElement->getValue()); //remove the dot at the 0 index
            $decisionMaker = $this->query->getClosureForAttribute($attributeName);

            $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        } elseif ($queryElement->getType() === Token::TOKEN_ELEMENT_WITH_ATTRIBUTE) {
            list($elementName, $attributeName) = explode('.', $queryElement->getValue());
            $decisionMaker = $this->query->getClosureForElementWithAttribute($attributeName, $elementName);
            $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        } else {
            throw new \Exception('Invalid query type');
        }

        return $fromElements;
    }

    /**
     * Get the query from the user's input.
     * Perform lexical and syntactical analysis and create the inner interpretation of the query.
     *
     * @throws InvalidQueryException
     */
    protected function createQuery()
    {
        $lexicalAnalyzer = new LexicalAnalyzer($this->config->getQuery());
        $syntacticalAnalyzer = new SyntacticalAnalyzer($lexicalAnalyzer->getTokens());

        if ($syntacticalAnalyzer->analyze() === false) {
            throw new InvalidQueryException('Syntax error in query');
        }

        $this->query = $syntacticalAnalyzer->getQuery();
        $this->query->validate();
    }

    /**
     * Get all elements from the $fromElement which meets the requirements defined by the condition from query.
     *
     * @param SimpleXMLElement $fromElement
     *
     * @return SimpleXMLElement[]
     *
     * @throws \Exception
     * @throws \InputFileException
     * @throws InvalidQueryException
     */
    protected function getSelectedElements(SimpleXMLElement $fromElement)
    {
        return $this->xmlParser->findSelectElements($fromElement);
    }

    /**
     * @throws \OutputFileException
     */
    protected function generateEmptyOutput()
    {
        $this->generateOutput([]);
    }

    /**
     * Limit the number of the elements by the number in the LIMIT clause.
     * Transform the elements into string and write the string to file or to the console output.
     *
     * @param SimpleXMLElement[] $elements
     *
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
}
