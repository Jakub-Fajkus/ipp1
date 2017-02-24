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


        $fromElements = $this->findFromElements();

        if (count($fromElements) < 1) {
            $this->generateEmptyOutput();

            return;
        }

        $selectElements = [];
        foreach ($fromElements as $fromElement) {
            $selectElements[] = $this->selectElements($fromElement);
        }

        //todo: rework or remove!
//        //clause where
        $document = new DOMDocument();
        $document->formatOutput = true;
//        foreach ($selectElements as $selectElement) {
//            $neco = dom_import_simplexml($selectElement);
//            $document->appendChild($document->importNode($neco, true));
//            //filter where !
//        }

        $xml = $document->saveXML();
//        file_put_contents($this->config->getOutputFileName(), $xml);
//        var_dump($test);
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
     * @return SimpleXMLIterator[]
     *
     * @throws Exception
     */
    protected function findFromElements()
    {
        $queryElement = $this->query->getFromElement();
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

        $fromElements = $this->xmlParser->findFromElements($decisionMaker, null, $findRoot);
        if (count($fromElements) > 0) {
            return $fromElements;
        } else {
            //todo: the from element was not found
            return;
        }
    }

    /**
     * @param SimpleXMLIterator $fromElement
     *
     * @return SimpleXMLIterator[]
     *
     * @throws InvalidQueryException
     */
    protected function selectElements(SimpleXMLIterator $fromElement)
    {
        $this->xmlParser->findSelectElements($fromElement);

//        $selectElement = $this->query->getSelectElement();
//        $findRoot = false;
//
//        if ($selectElement->getType() === Token::TOKEN_ELEMENT) {
//            $selectElementName = $selectElement->getValue();
//            $strategy = $this->getStrategyForQuery();
//            $decisionMaker = function (SimpleXMLIterator $rootElement, $attributes) use ($selectElementName, $strategy) {
//
//
//
//                return $rootElement->getName() === $selectElementName;
//            };
//        } else {
//            throw new InvalidQueryException('Invalid select element');
//        }
//
//        $fromElement->rewind();
//        $foundElements = $this->xmlParser->findFromElements($decisionMaker, $fromElement, $findRoot, false, true);
//
//        if ($this->query->getConditionLeft() === '') {
//            return $foundElements;
//        }
//
//        return $this->filterSelectElements($foundElements);
    }

//    /**
//todo: delete!
//     * @param $elements
//     *
//     * @return SimpleXMLIterator[]
//     *
//     * @throws InvalidQueryException
//     */
//    protected function filterSelectElements($elements)
//    {
//        //dva pripady:
//        //1. element v SELECT je shodny s elementem v WHERE -> hledame vsehcny elementy, ktere maji tuto vlasnost(hodnotu atributu nebo elementu samotneho)
//        //2. element ve WHERE je podlementem elementu v SELECT - hledame v podlelementech
//
//        $strategy = $this->getStrategyForQuery();
//        $filteredElements = [];
//
//        //the element in where is identical to the element in the select
//        if ($this->query->getSelectElement()->getValue() === $this->query->getConditionLeft()->getValue()) {
//            foreach ($elements as $element) {
//                if ($strategy->meetsCondition($element)) {
//                     $filteredElements[] = $element;
//                }
//            }
//        } else {
//            //todo: this will be funnier!
//        }
//
//        return $filteredElements;
//
//    }

    protected function generateEmptyOutput()
    {
        //todo generate output file(program parameters!)
    }
}
