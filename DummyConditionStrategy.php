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
     * @param SimpleXMLIterator $element
     * @return bool
     */
    public function meetsCondition(SimpleXMLIterator $element)
    {
        return $this->returnValue;
    }
}
