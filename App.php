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
     * @param Output $output
     */
    public function __construct(Config $config, Output $output)
    {
        $this->config = $config;
        $this->output = $output;
    }

    /**
     * @throws \InvalidQueryException
     * @throws \InputFileException
     * @throws \Exception
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
        $this->xmlParser = new XMLParser($this->config->getInputFileName(), $this->query);

        if ($this->query->getFromElement() === null) {
            $this->generateEmptyOutput();

            return;
        }

        $fromElements = $this->findFromElements();

        if (count($fromElements) < 1) {
            $this->generateEmptyOutput();

            return;
        }

        $selectElements = [];
        foreach ($fromElements as $fromElement) {
            $selectElements = array_merge($selectElements, $this->selectElements($fromElement));
        }

        //todo: order and limit?

        $this->saveDomToFile($selectElements);
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

    /**
     * @param Query     $query
     * @param XMLParser $$this->xmlParser
     *
     * @return SimpleXMLElement[]
     *
     * @throws Exception
     */
    protected function findFromElements()
    {
        $queryElement = $this->query->getFromElement();
        $findRoot = false;

        if ($queryElement->getType() === Token::TOKEN_ELEMENT) {
            $queryElementName = $queryElement->getValue();
            $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($queryElementName) {
                return $rootElement->getName() === $queryElementName;
            };

            $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        } elseif ($queryElement->getType() === Token::TOKEN_ATTRIBUTE) {
            $attributeName = str_replace('.', '', $queryElement->getValue()); //remove the dot at the 0 index
            $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($attributeName) {
                foreach ($attributes as $key => $value) {
                    if ($key === $attributeName) {
                        return true;
                    }
                }

                return false;
            };

            $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        } elseif ($queryElement->getType() === Token::TOKEN_ELEMENT_WITH_ATTRIBUTE) {
            list($elementName, $attributeName) = explode('.', $queryElement->getValue());
            $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($attributeName, $elementName) {
                if ($rootElement->getName() !== $elementName) {
                    return false;
                }

                foreach ($attributes as $key => $value) {
                    if ($key === $attributeName) {
                        return true;
                    }
                }

                return false;
            };

            $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        } elseif ($queryElement->getType() === Token::TOKEN_ROOT) {
            $fromElements = $this->xmlParser->getIterator(); //get the root
        } else {
            throw new \Exception('Invalid query type');
        }

        return $fromElements;
    }

    /**
     * @param SimpleXMLElement $fromElement
     *
     * @return SimpleXMLElement[]
     *
     * @throws InvalidQueryException
     */
    protected function selectElements(SimpleXMLElement $fromElement)
    {
        return $this->xmlParser->findSelectElements($fromElement);
    }

    protected function generateEmptyOutput()
    {
        $this->saveDomToFile([]);
    }

    /**
     * @param SimpleXMLElement[] $elements
     */
    protected function saveDomToFile($elements) {
//        $this->config->setRootElementName('mujRoot'); //todo: testing
//        $this->config->setGenerateXmlHeader(false);

        $document = new DOMDocument();
        $emptyDocumentHeader = $document->saveXML();
        $document->formatOutput = true;
        $rootElement = null; //either the whole document or the artificial root

        if ($this->config->getRootElementName() !== '') {
            $rootElement = $document->createElement($this->config->getRootElementName());
            $document->appendChild($rootElement);
        } else {
            $rootElement = $document;
        }

        foreach ($elements as $selectElement) {
            $node = dom_import_simplexml($selectElement);
            $rootElement->appendChild($document->importNode($node, true));
        }

        if ($this->config->isGenerateXmlHeader()) {
            $xml = $document->saveXML(null, LIBXML_NOEMPTYTAG);
        } else {
            $xml = str_replace($emptyDocumentHeader, '', $document->saveXML(null, LIBXML_NOEMPTYTAG));
        }

        echo "TUUU";
        var_dump($xml);


        if ($this->config->getOutputFileName() === '') {
            //write to the stdout
            $this->output->writeStdout($xml);
        } elseif (false === file_put_contents($this->config->getOutputFileName(), $xml)) {
            throw new OutputFileException('Can not write to the output file');
        }
    }
}
