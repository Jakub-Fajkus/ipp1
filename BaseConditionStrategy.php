<?php

/**
 * Class BaseConditionStrategy
 * 
 * The class is used to determine if the given element meets the condition form the query
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


    abstract public function meetsCondition(SimpleXMLElement $element);

    /**
     * @return array
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
}