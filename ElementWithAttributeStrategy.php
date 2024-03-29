<?php


/**
 * Class ElementWithAttributeStrategy.
 *
 * Used when the element in the condition is in the format: element.attribute
 */
class ElementWithAttributeStrategy extends BaseConditionStrategy
{
    /**
     * @param SimpleXMLElement $element
     *
     * @return bool
     * @throws \InvalidQueryException
     */
    public function meetsCondition(SimpleXMLElement $element)
    {
        $name = $element->getName();

        //no condition
        if ($this->query->getConditionLeft() === null) {
            if ($name === $this->query->getSelectElement()->getValue()) {
                return true;
            }
        } else {
            //the actual element is the select element and it has the wanted property(attribute with the value in this case)
            if ($name === $this->query->getSelectElement()->getValue()) {
                $elementNameFromCondition = $this->getElementName($this->query->getConditionLeft()->getValue());

                if ($elementNameFromCondition === $name
                    && ElementUtils::hasAttribute($element, $this->getAttributeName())
                    && $this->query->evaluateQuery($this->getAttributeValue($element))
                ) {
                    $this->selectedElements[] = $element;

                    return true;
                } else {
                    //go deeper
                    $thisStrategy = $this;
                    $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($thisStrategy) {
                        return $thisStrategy->meetsCondition($rootElement);
                    };
                    //now, look deeper and find the element from the where clause(if present)
                    $this->lookDeeper($decisionMaker, $element, true);
                }
                //find subelement of the element which meets condition
            } else {
                $thisStrategy = $this;

                $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($thisStrategy) {
                    return $thisStrategy->meetsCondition($rootElement);
                };

                if ($name === $this->getElementName($this->query->getConditionLeft()->getValue())
                    && ElementUtils::hasAttribute($element, $this->getAttributeName())
                    && $this->query->evaluateQuery($this->getAttributeValue($element))
                ) {
                    return true;
                }

                return $this->lookDeeper($decisionMaker, $element, false);
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getAttributeName()
    {
        $conditionElementName = $this->query->getConditionLeft()->getValue();
        return mb_substr($conditionElementName, mb_strpos($conditionElementName, '.') + 1);
    }

    /**
     * @param SimpleXMLElement $element
     *
     * @return mixed
     */
    protected function getAttributeValue(SimpleXMLElement $element)
    {
        return (string)$element->attributes()[$this->getAttributeName()][0];
    }

    /**
     * @param $name string Element with attribute name, e.g. element.attribute
     *
     * @return string element
     */
    protected function getElementName($name)
    {
        return mb_substr($name, 0, mb_strpos($name, '.'));
    }
}
