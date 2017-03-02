<?php


class Query
{
    /**
     * @var Token
     */
    protected $selectElement;

    /**
     * @var Token
     */
    protected $fromElement;

    /**
     * @var Token
     */
    protected $conditionLeft;

    /**
     * @var Token
     */
    protected $conditionOperator;

    /**
     * @var Token
     */
    protected $conditionRight;

    /**
     * @var Token
     */
    protected $orderElement;

    /**
     * @var Token
     */
    protected $ordering;

    /**
     * @var bool
     */
    protected $negateCondition;

    /**
     * @var Token
     */
    protected $limit;

    /**
     * @var bool
     */
    protected $isFromElementEmpty;

    /**
     * Query constructor.
     */
    public function __construct()
    {
        $this->negateCondition = false;
        $this->isFromElementEmpty = false;
    }

    /**
     * Validate the query with constraints which can not be validated in the setters.
     *
     * @throws \InvalidQueryException
     */
    public function validate()
    {
        //can not apply CONTAINS on int(Invalid query)
        if ($this->conditionOperator !== null
            && $this->conditionOperator->getType() === Token::TOKEN_CONTAINS
            && $this->conditionRight->getType() !== Token::TOKEN_STRING
        ) {
            throw new InvalidQueryException('Can not use CONTAINS with number literal');
        }

        if ($this->fromElement === '') {
            $this->isFromElementEmpty = true;
        }
    }

    /**
     * @return Token
     */
    public function getSelectElement()
    {
        return $this->selectElement;
    }

    /**
     * @param Token $selectElement
     */
    public function setSelectElement(Token $selectElement)
    {
        $this->selectElement = $selectElement;
    }

    /**
     * @return Token
     */
    public function getFromElement()
    {
        return $this->fromElement;
    }

    /**
     * @param Token $fromElement
     */
    public function setFromElement(Token $fromElement)
    {
        $this->fromElement = $fromElement;
    }

    /**
     * @return Token
     */
    public function getConditionLeft()
    {
        return $this->conditionLeft;
    }

    /**
     * @param Token $conditionLeft
     *
     * @throws \InvalidQueryException
     */
    public function setConditionLeft(Token $conditionLeft)
    {
        if (!$conditionLeft->isElementOrAttribute()) {
            throw new InvalidQueryException('The left side of condition must be element or attribute');
        }

        $this->conditionLeft = $conditionLeft;
    }

    /**
     * @return Token
     */
    public function getConditionOperator()
    {
        return $this->conditionOperator;
    }

    /**
     * @param Token $conditionOperator
     *
     * @throws \InvalidQueryException
     */
    public function setConditionOperator(Token $conditionOperator)
    {
        if (!$conditionOperator->isConditionOperator()) {
            throw new InvalidQueryException('The condition operator is not valid');
        }

        $this->conditionOperator = $conditionOperator;
    }

    /**
     * @return Token
     */
    public function getConditionRight()
    {
        return $this->conditionRight;
    }

    /**
     * @param Token $conditionRight
     *
     * @throws \InvalidQueryException
     */
    public function setConditionRight(Token $conditionRight)
    {
        if (!$conditionRight->isLiteral()) {
            throw new InvalidQueryException('The right condition operand must be a literal');
        }

        $this->conditionRight = $conditionRight;
    }

    /**
     * @return Token
     */
    public function getOrderElement()
    {
        return $this->orderElement;
    }

    /**
     * @param Token $orderElement
     *
     * @throws \InvalidQueryException
     */
    public function setOrderElement(Token $orderElement)
    {
        if (!$orderElement->isElementOrAttribute()) {
            throw new InvalidQueryException('The order element must be element or attribute');
        }

        $this->orderElement = $orderElement;
    }

    /**
     * @return Token
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     * @param Token $ordering
     *
     * @throws \InvalidQueryException
     */
    public function setOrdering(Token $ordering)
    {
        if (!($ordering->getValue() === Token::TOKEN_ASC || $ordering->getValue() === Token::TOKEN_DESC)) {
            throw new InvalidQueryException('The ordering is not valid');
        }

        $this->ordering = $ordering;
    }

    public function negateCondition()
    {
        $this->negateCondition = !$this->negateCondition;
    }

    /**
     * @return Token
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param Token $limit
     *
     * @throws \InvalidQueryException
     */
    public function setLimit(Token $limit)
    {
        if (!(is_int($limit->getValue()) && $limit->getValue() >= 0)) {
            throw new InvalidQueryException('The limit iteral must be positive integer or zero');
        }

        $this->limit = $limit;
    }

    /**
     * @param $sourceValue
     * @return bool
     * @throws InvalidQueryException
     */
    public function evaluateQuery($sourceValue)
    {
        $return = false;

        if ($this->conditionRight->getType() === Token::TOKEN_STRING) {
            if ($this->conditionOperator->getType() === Token::TOKEN_OPERATOR_LESS) {
                $return = strcmp($sourceValue, $this->conditionRight->getValue()) < 0;
            } elseif ($this->conditionOperator->getType() === Token::TOKEN_OPERATOR_MORE) {
                $return = strcmp($sourceValue, $this->conditionRight->getValue()) > 0;
            } elseif ($this->conditionOperator->getType() === Token::TOKEN_OPERATOR_EQUALS) {
                $return = strcmp($sourceValue, $this->conditionRight->getValue()) === 0;
            } elseif ($this->conditionOperator->getType() === Token::TOKEN_CONTAINS) {
                $return = strpos($sourceValue, $this->conditionRight->getValue()) !== false;
            }
        } elseif ($this->conditionRight->getType() === Token::TOKEN_INTEGER) {
            $sourceValue = $this->getNumericValue($sourceValue);

            if ($this->conditionOperator->getType() === Token::TOKEN_OPERATOR_LESS) {
                $return = $sourceValue < $this->conditionRight->getValue();
            } elseif ($this->conditionOperator->getType() === Token::TOKEN_OPERATOR_MORE) {
                $return = $sourceValue > $this->conditionRight->getValue();
            } elseif ($this->conditionOperator->getType() === Token::TOKEN_OPERATOR_EQUALS) {
                $return = $sourceValue == $this->conditionRight->getValue();
            } elseif ($this->conditionOperator->getType() === Token::TOKEN_CONTAINS) {
                throw new InvalidQueryException('Contains cannot be used for integers');
            }
        }

        if ($this->negateCondition === true) {
            return !$return;
        } else {
            return $return;
        }
    }

    /**
     * @return BaseConditionStrategy
     * @throws \InvalidInputFileFormatException
     *
     * @throws \InputFileException
     * @throws InvalidQueryException
     */
    public function getStrategy()
    {
        $strategyParser = new XMLParser(null, $this);

        if ($this->getConditionLeft() === null) {
            return new ElementConditionStrategy($this, $strategyParser);
        }

        if ($this->getConditionLeft()->getType() === Token::TOKEN_ELEMENT) {
            return new ElementConditionStrategy($this, $strategyParser);
        } elseif ($this->getConditionLeft()->getType() === Token::TOKEN_ATTRIBUTE) {
            return new AttributeConditionStrategy($this, $strategyParser);
        } elseif ($this->getConditionLeft()->getType() === Token::TOKEN_ELEMENT_WITH_ATTRIBUTE) {
            return new ElementWithAttributeStrategy($this, $strategyParser);
        } else {
            throw new InvalidQueryException('The element in condition is not valid');
        }
    }

    /**
     * @param string|int|float $sourceValue
     *
     * @return bool
     */
    protected function isDouble($sourceValue)
    {
        $matches = [];
        $ret = preg_match('/\s*[+|-]?\d+\.\d+/', $sourceValue, $matches);

        return $ret === 1;
    }

    /**
     * @param $sourceValue
     *
     * @return float|int
     *
     * @throws InvalidQueryException
     */
    protected function getNumericValue($sourceValue)
    {
        if (is_numeric($sourceValue)) {
            if ($this->isDouble($sourceValue)) {
                return (double)$sourceValue;
            } elseif (is_int($sourceValue)) {
                return (int)$sourceValue;
            } else {
                throw new InvalidQueryException("Could not use string: $sourceValue as a number");
            }
        } else {
            throw new InvalidQueryException("Could not use string: $sourceValue as a number");
        }
    }
}
