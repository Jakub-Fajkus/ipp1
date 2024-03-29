<?php


/**
 * Class ElementConditionStrategy
 *
 * Used when the element in the condition is in the format: element
 */
class ElementConditionStrategy extends BaseConditionStrategy
{
    /**
     * Check if the element meets the condition from the query.
     *
     * @param SimpleXMLElement $element
     *
     * @return bool
     *
     * @throws InvalidInputFileFormatException
     */
    public function meetsCondition(SimpleXMLElement $element)
    {
        $name = $element->getName();

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
                    throw new InvalidInputFileFormatException(
                        "The element $name contains other elements! Thus it cannot be used in the condition."
                    );
                } else {
                    $value = (string)$element;
                }

                return $this->query->evaluateQuery($value); //perform query evaluation

            //find subelement of the element which meets condition
            } else {
                return $this->goDeeper($element);
            }
        }
    }

    /**
     * @param SimpleXMLElement $element
     *
     * @return bool
     */
    protected function goDeeper(SimpleXMLElement $element)
    {
        $thisStrategy = $this;

        $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($thisStrategy) {
            return $thisStrategy->meetsCondition($rootElement);
        };

        //did we found the element we are searching for?
        if ($element->getName() === $this->query->getSelectElement()->getValue()) {
            //now, look deeper and find the element from the where clause(if present)
            return $this->lookDeeper($decisionMaker, $element, true);
        } else {
            return $this->lookDeeper($decisionMaker, $element, false);
        }
    }
}