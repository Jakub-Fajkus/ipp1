<?php

/**
 * Class XMLParser.
 *
 * Class implementing a depth search of the xml tree.
 */
class XMLParser
{
    /**
     * @var SimpleXMLElement
     */
    protected $root;

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
                $this->root = @new SimpleXMLElement($xmlString, 0);
            } catch (\Exception $exception) {
                throw new InvalidInputFileFormatException(
                    'Could not parse input file: '.$xmlString.'. More info: '.$exception->getMessage()
                );
            }
        }

        $this->query = $query;
    }

    /**
     * Find all elements for that the $decisionMaker returns true
     *
     * @param Closure               $decisionMaker Closure which is used to determine whether the element
     *                                             is the one which we are looking for or not
     * @param SimpleXMLElement|null $root
     *
     * @return SimpleXMLElement[]
     *
     * @throws \Exception
     */
    public function findFromElements(Closure $decisionMaker, SimpleXMLElement $root = null, $checkRoot = true, $goDeeper = true)
    {
        if ($this->root === null && $root === null) {
            throw new \Exception('No xml data to filter');
        }

        if ($root === null) {
            $root = $this->root; //root root
        }

        //check if we are looking for the root element
        $attributes = ElementUtils::getAttributes($root);
        if ($checkRoot && $decisionMaker($root, $attributes)) {
            return [$root];
        }

        $returnElements = [];

        /** @var SimpleXMLElement $child */
        foreach ($root->children() as $child) {
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
     * Find all elements from the $fromElement which meets the requirements defined by the condition from query.
     *
     * @param SimpleXMLElement $fromElement
     *
     * @return array
     *
     * @throws \InvalidInputFileFormatException
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

        return $strategy->getSelectedElements();
    }

    /**
     * @return SimpleXMLElement
     */
    public function getRoot()
    {
        return $this->root;
    }
}
