<?php


/**
 * Class ElementConditionStrategy
 */
class ElementConditionStrategy extends BaseConditionStrategy
{

    public function meetsCondition(SimpleXMLElement $element)
    {
        $name = $element->getName();
        $children = $element->children();
        $attributes = $element->attributes();
        if ($name === $this->query->getConditionLeft()->getValue()) {
            var_dump($children);

            if (count($element->children()) > 0) {
                throw new InvalidInputFileFormatException("The element $name contains other elements! Thus it cannot be used in the condition.");
            } else {
                $value = (string)$element;
            }


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