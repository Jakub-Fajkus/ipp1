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

        //no condition
        if ($this->query->getConditionLeft() === null) {
            if ($name === $this->query->getSelectElement()->getValue()) {
                $this->selectedElements[] = $element;
                return true;
            } else {
                return $this->goDeeper($element);
            }

        } else {
            //the select element is in the condition
            if ($name === $this->query->getConditionLeft()->getValue()) {
                // the element in condition can not have any subelement!
                if (count($element->children()) > 0) {
                    throw new InvalidInputFileFormatException("The element $name contains other elements! Thus it cannot be used in the condition.");
                } else {
                    $value = (string)$element;
                }

                $returnValue = $this->query->evaluateQuery($value); //perform query evaluation

                return $returnValue;

            //find subelement of the element which meets condition
            } else {
                return $this->goDeeper($element);
            }
        }

        return false;
    }

    protected function goDeeper(SimpleXMLElement $element)
    {
        $thisStrategy = $this;

        $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($thisStrategy) {
            return $thisStrategy->meetsCondition($rootElement);
        };

        //did we found the element we are searching for?
        if ($element->getName() === $this->query->getSelectElement()->getValue()) {
            //now, look deeper and find the element from the where clause(if present)
            $subElements = $this->xmlParser->findFromElements($decisionMaker, $element, false, true);

            if (count($subElements) > 0) {
                $this->selectedElements[] = $element;

                return true;
            } else {
                return false;
            }
        } else {
            $subElements = $this->xmlParser->findFromElements($decisionMaker, $element, false, true);

            if (count($subElements) > 0) {
                return true;
            } else {
                return false;
            }
        }
    }
}