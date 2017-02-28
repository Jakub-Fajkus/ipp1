<?php

/**
 * Class AttributeConditionStrategy
 */
class AttributeConditionStrategy extends BaseConditionStrategy
{
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

        return false;
    }

    /**
     * @param SimpleXMLElement $element
     * @return bool
     */
    protected function hasAttribute(SimpleXMLElement $element)
    {
        $attributeName = str_replace('.', '', $this->query->getConditionLeft()->getValue()); //remove the dot

        foreach ($this->xmlParser->getAttributes($element) as $key => $value) {
            if ($key === $attributeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param SimpleXMLElement $element
     * @return mixed
     */
    protected function getAttributeValue(SimpleXMLElement $element)
    {
        $attributeName = str_replace('.', '', $this->query->getConditionLeft()->getValue()); //remove the dot


        //todo: this may return the SimpleXMLElement instance!!!
        return $element->attributes()[$attributeName];
    }
}