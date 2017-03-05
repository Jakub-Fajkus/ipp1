<?php

/**
 * Class BaseConditionStrategy
 * 
 * The class is used to determine if the given element meets the condition form the query.
 *
 * All subclases should be used as a parameter for the XMLParser class.
 */
abstract class BaseConditionStrategy
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var XMLParser
     */
    protected $xmlParser;


    /**
     * @var array SimpleXMLElement
     */
    protected $selectedElements = [];

    /**
     * BaseConditionStrategy constructor.
     * @param Query $query
     * @param XMLParser $xmlParser
     */
    public function __construct(Query $query, XMLParser $xmlParser)
    {
        $this->query = $query;
        $this->xmlParser = $xmlParser;
    }

    /**
     * Check if the element meets the condition from the query.
     *
     * @param SimpleXMLElement $element
     *
     * @return bool
     */
    abstract public function meetsCondition(SimpleXMLElement $element);

    /**
     * @return SimpleXMLElement[]
     */
    public function getSelectedElements()
    {
        return $this->selectedElements;
    }

    /**
     * @param array $selectedElements
     */
    public function setSelectedElements($selectedElements)
    {
        $this->selectedElements = $selectedElements;
    }

    /**
     * Lok deeper into the tree.
     *
     * @param $decisionMaker
     * @param $element
     * @param bool $addToSelected
     *
     * @return bool
     */
    protected function lookDeeper($decisionMaker, $element, $addToSelected = true)
    {
        $subElements = $this->xmlParser->findFromElements($decisionMaker, $element, false, true);

        if (count($subElements) > 0) {
            if ($addToSelected === true) {
                $this->selectedElements[] = $element;
            }

            return true;
        } else {
            return false;
        }
    }
}
