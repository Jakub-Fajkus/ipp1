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

        $syntacticalAnalyzer->analyze();
        $query = $syntacticalAnalyzer->getQuery();
        $query->validate();

        $parser = new XMLParser($this->config->getInputFileName());
        $fromElement = $this->findFromElement($query, $parser);

        var_dump($fromElement);
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
     * @param XMLParser $parser
     *
     * @throws Exception
     */
    protected function findFromElement(Query $query, XMLParser $parser)
    {
        $queryElement = $query->getFromElement();
        $findRoot = false;

        if ($queryElement->getType() === Token::TOKEN_ELEMENT) {
            $queryElementName = $queryElement->getValue();
            $decisionMaker = function (SimpleXMLIterator $rootElement, $attributes) use ($queryElementName) {
                return $rootElement->getName() === $queryElementName;
            };
        } elseif ($queryElement->getType() === Token::TOKEN_ATTRIBUTE) {
            $attributeName = str_replace('.', '', $queryElement->getValue()); //remove the dot at the 0 index
            $decisionMaker = function (SimpleXMLIterator $rootElement, $attributes) use ($attributeName) {
                foreach ($attributes as $key => $value) {
                    if ($key === $attributeName) {
                        return true;
                    }
                }

                return false;
            };
        } elseif ($queryElement->getType() === Token::TOKEN_ELEMENT_WITH_ATTRIBUTE) {
            list($elementName, $attributeName) = explode('.', $queryElement->getValue());
            $decisionMaker = function (SimpleXMLIterator $rootElement, $attributes) use ($attributeName, $elementName) {
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
        } elseif ($queryElement->getType() === Token::TOKEN_ROOT) {
            $decisionMaker = function () {
                return false;
            };

            $findRoot = true;
        } else {
            throw new \Exception('Invalid query type');
        }

        return $parser->findElement($decisionMaker, null, $findRoot);
    }
}
