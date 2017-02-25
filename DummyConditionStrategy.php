<?php

/**
 * Class DummyConditionStrategy
 */
class DummyConditionStrategy extends BaseConditionStrategy
{
    /**
     * @var bool
     */
    protected $returnValue;

    public function __construct(Query $query, $returnValue)
    {
        parent::__construct($query);

        $this->returnValue = $returnValue;
    }


    /**
     * @param SimpleXMLElement $element
     * @return bool
     */
    public function meetsCondition(SimpleXMLElement $element)
    {
        return $this->returnValue;
    }
}
