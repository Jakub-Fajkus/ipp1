<?php


/**
 * Class ElementWithAttributeStrategy.
 */
class ElementWithAttributeStrategy extends BaseConditionStrategy
{
    /**
     * @param SimpleXMLElement $element
     *
     * @return bool
     */
    public function meetsCondition(SimpleXMLElement $element)
    {
        $name = $element->getName();
        $children = $element->children();
        $attributes = $element->attributes();

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
                    && $this->hasAttribute($element)
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
                    $subElements = $this->xmlParser->findFromElements($decisionMaker, $element, false, true);

                    if (count($subElements) > 0) {
                        $this->selectedElements[] = $element;

                        return true;
                    } else {
                        return false;
                    }
                }
                //find subelement of the element which meets condition
            } else {
                $thisStrategy = $this;

                $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($thisStrategy) {
                    return $thisStrategy->meetsCondition($rootElement);
                };

                if ($name === $this->getElementName($this->query->getConditionLeft()->getValue())
                    && $this->hasAttribute($element)
                    && $this->query->evaluateQuery($this->getAttributeValue($element))
                ) {
//                    $this->selectedElements[] = $element; //todo: remove! - this adds the element, which is defined i the where clause :D

                    return true;
                }

                $subElements = $this->xmlParser->findFromElements($decisionMaker, $element, false, true);

                if (count($subElements) > 0) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * @param SimpleXMLElement $element
     *
     * @return bool
     */
    protected function hasAttribute(SimpleXMLElement $element)
    {
        $attributeName = $this->getAttributeName();

        foreach ($this->xmlParser->getAttributes($element) as $key => $value) {
            if ($key === $attributeName) {
                return true;
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
        $val = (string)$element->attributes()[$this->getAttributeName()][0];
        return $val;
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
