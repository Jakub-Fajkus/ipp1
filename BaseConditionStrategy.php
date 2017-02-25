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
     * BaseConditionStrategy constructor.
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    abstract public function meetsCondition(SimpleXMLElement $element);
}