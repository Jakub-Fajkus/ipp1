<?php


/**
 * Class ElementConditionStrategy
 */
class ElementConditionStrategy extends BaseConditionStrategy
{

    public function meetsCondition(SimpleXMLIterator $element)
    {
        $name = $element->getName();

        if ($name === $this->query->getConditionLeft()->getValue()) {
            $children = $element->getChildren();
            $attributes = $element->attributes();
            $key = $element->key();
            $value = (string)$element;


            $returnValue = $this->query->evaluateQuery($value); //perform query evaluation

            return $returnValue;
        } else {
            return false;
        }

        //get the element value(no more elements!!
        //check for datatypes for operator(contains in int, ...)



//        if ($element->chi)
    }
}