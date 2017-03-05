<?php

/**
 * Class AttributeConditionStrategy
 *
 * Used when the element in the condition is in the format: .attribute
 */
class AttributeConditionStrategy extends BaseConditionStrategy
{
    public function meetsCondition(SimpleXMLElement $element)
    {
        $name = $element->getName();

        //no condition
        if ($this->query->getConditionLeft() === null) {
            if ($name === $this->query->getSelectElement()->getValue()) {
                return true;
            }
        } else {
            //the actual element is the select element
            if ($name === $this->query->getSelectElement()->getValue()) {
                if ($this->hasAttribute($element) && $this->query->evaluateQuery($this->getAttributeValue($element))) {
                    $this->selectedElements[] = $element;

                    return true;
                }

                return false;
                //find subelement of the element which meets condition
            } else {
                $thisStrategy = $this;

                $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($thisStrategy) {
                    return $thisStrategy->meetsCondition($rootElement);
                };

                //did we found the element we are searching for?
                if ($name === $this->query->getSelectElement()->getValue()) {
                    //now, look deeper and find the element from the where clause(if present)
                    return $this->lookDeeper($decisionMaker, $element, true);
                } else {
                    return $this->lookDeeper($decisionMaker, $element, false);
                }
            }
        }

        return false;
    }

    /**
     * @param SimpleXMLElement $element
     * @return bool
     */
    protected function hasAttribute(SimpleXMLElement $element)
    {
        return ElementUtils::hasAttribute($element, $this->query->getConditionLeft()->getValue());
    }

    /**
     * @param SimpleXMLElement $element
     * @return mixed
     */
    protected function getAttributeValue(SimpleXMLElement $element)
    {
        //todo: this may return the SimpleXMLElement instance!!!
        return ElementUtils::getAttributeValue($element, $this->query->getConditionLeft()->getValue());
    }
}