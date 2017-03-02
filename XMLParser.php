<?php


class XMLParser
{
    /**
     * @var SimpleXMLElement
     */
    protected $iterator;

    /**
     * @var Query
     */
    protected $query;

    /**
     * XMLParser constructor.
     *
     * @param $xmlString
     * @param Query $query
     *
     * @throws InvalidInputFileFormatException
     * @throws InputFileException
     */
    public function __construct($xmlString, $query)
    {
        if (!empty($xmlString)) {
            try {
                $this->iterator = @new SimpleXMLElement($xmlString, 0);
            } catch (\Exception $exception) {
                throw new InvalidInputFileFormatException(
                    'Could not parse input file: ' . $xmlString . '. More info: ' . $exception->getMessage()
                );
            }
        }

        $this->query = $query;
    }

    /**
     * kazde volani children vrati iterator pro zanorene elementy.
     *
     * postup hledani:
     * 1. iterace hned po rewind -> hledame v rootu
     * 2. iterace root->getChildren -> hledani v zanoreni
     *
     * @param Closure $decisionMaker Closure which is used to determine whether the element
     * is the one which we are looking for or not
     * @param SimpleXMLElement|null $iterator
     *
     * @return SimpleXMLElement[]
     * @throws \Exception
     */
    public function findFromElements(Closure $decisionMaker, SimpleXMLElement $iterator = null, $checkRoot = true, $goDeeper = true)
    {
        if ($this->iterator === null && $iterator === null) {
            throw new \Exception('No xml data to filter');
        }

        if ($iterator === null) {
            $iterator = $this->iterator; //root iterator
        }

        $nameRoot = $iterator->getName(); //debug

        //check if we are looking for the root element
        $attributes = ElementUtils::getAttributes($iterator);
        if ($checkRoot && $decisionMaker($iterator, $attributes)) {
            return [$iterator];
        }

        $returnElements = [];

        /** @var SimpleXMLElement $child */
        foreach ($iterator->children() as $child) {
            $childName = $child->getName(); //debug
            if ($child === null) {
                break;
            }

            $attributes = ElementUtils::getAttributes($child);
            if ($decisionMaker($child, $attributes)) {
                $returnElements[] = $child;
            } elseif ($goDeeper) {
                $foundElements = $this->findFromElements($decisionMaker, $child);
                $returnElements = array_merge($returnElements, $foundElements);
            }
        }

        return $returnElements;
    }

    /**
     * @param SimpleXMLElement $fromElement
     * @return array
     *
     * @throws \InputFileException
     * @throws \Exception
     * @throws InvalidQueryException
     */
    public function findSelectElements(SimpleXMLElement $fromElement)
    {
        $selectElement = $this->query->getSelectElement();
        if ($selectElement->getType() !== Token::TOKEN_ELEMENT) {
            throw new InvalidQueryException('Invalid select element');
        }

        $selectElementName = $selectElement->getValue();
        $strategy = $this->query->getStrategy();


        $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($selectElementName, $strategy) {
            return $strategy->meetsCondition($rootElement);
        };

        $this->findFromElements($decisionMaker, $fromElement, true, false);
        $foundElements = $strategy->getSelectedElements(); //debug variable

        return $foundElements;
    }

    /**
     * @return SimpleXMLElement
     */
    public function getIterator()
    {
        return $this->iterator;
    }
}
