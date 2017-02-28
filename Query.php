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
        //Na celočíselný literál nelze aplikovat relační operátor CONTAINS (chyba dotazu).
        if ($this->conditionOperator === Token::TOKEN_CONTAINS &&
            $this->conditionRight->getType() !== Token::TOKEN_STRING) {
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

    public function evaluateQuery($sourceValue)
    {
        $return = false;

        if ($this->conditionRight->getType() === Token::TOKEN_STRING) {
            if ($this->conditionOperator->getValue() === Token::TOKEN_OPERATOR_LESS) {
                $return = strcmp($sourceValue, $this->conditionRight->getValue()) < 0;
            } elseif ($this->conditionOperator->getValue() === Token::TOKEN_OPERATOR_MORE) {
                $return = strcmp($sourceValue, $this->conditionRight->getValue()) > 0;
            } elseif ($this->conditionOperator->getValue() === Token::TOKEN_OPERATOR_EQUALS) {
                $return = strcmp($sourceValue, $this->conditionRight->getValue()) === 0;
            } elseif ($this->conditionOperator->getValue() === Token::TOKEN_CONTAINS) {
                $return = strpos($sourceValue, $this->conditionRight->getValue()) !== false;
            }
        } elseif ($this->conditionRight->getType() === Token::TOKEN_INTEGER) {
            if (is_numeric($sourceValue)) {
                if ($this->isDouble($sourceValue)) {
                    $sourceValue = (double)$sourceValue;
                } elseif (is_int($sourceValue)) {
                    $sourceValue = (int)$sourceValue;
                } else {
                    //todo? throw? or what? the string is not a number
                    throw new InvalidQueryException("Could not use string: $sourceValue as a number");
                }
            } else {
                throw new InvalidQueryException("Could not use string: $sourceValue as a number");
            }

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

    protected function isDouble($sourceValue)
    {
        $matches = [];
        $ret = preg_match('/\s*[+|-]?\d+\.\d+/', $sourceValue, $matches);
        return $ret === 1;
    }
}
